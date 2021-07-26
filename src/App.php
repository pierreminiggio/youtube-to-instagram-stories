<?php

namespace PierreMiniggio\YoutubeToInstagramStories;

use PierreMiniggio\DatabaseFetcher\DatabaseFetcher;
use PierreMiniggio\GithubActionRemotionRenderer\GithubActionRemotionRenderer;
use PierreMiniggio\GithubActionRemotionRenderer\GithubActionRemotionRendererException;
use PierreMiniggio\InstagramStoryPoster\InstagramStoryPoster;
use PierreMiniggio\InstagramStoryPoster\InstagramStoryPosterException;
use PierreMiniggio\YoutubeToInstagramStories\Connection\DatabaseConnectionFactory;
use PierreMiniggio\YoutubeToInstagramStories\Repository\LinkedChannelRepository;
use PierreMiniggio\YoutubeToInstagramStories\Repository\NonUploadedVideoRepository;
use PierreMiniggio\YoutubeToInstagramStories\Repository\StoryToPostRepository;

class App
{
    public function run(): int
    {
        $config = require(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'config.php');

        $cacheDir = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR;

        if (! file_exists($cacheDir)) {
            mkdir($cacheDir);
        }

        $cacheUrl = $config['cache_url'];

        if (empty($config['db'])) {
            echo 'Missing database config';

            return 0;
        }

        $databaseConnection = (new DatabaseConnectionFactory())->makeFromConfig($config['db']);
        $fetcher = new DatabaseFetcher($databaseConnection);
        $channelRepository = new LinkedChannelRepository($fetcher);
        $nonUploadedVideoRepository = new NonUploadedVideoRepository($databaseConnection);
        $storyToPostRepository = new StoryToPostRepository($databaseConnection);

        $channels = $channelRepository->findAll();

        $actionRenderer = new GithubActionRemotionRenderer();

        $pierreMiniggioInstagramActionUploaderAccountName = 'PIERREMINIGGIO';
        $pierreMiniggioRendererProjects = $config['pierreMiniggioRendererProjects'];
        $pierreMiniggioRendererProject = $pierreMiniggioRendererProjects[
            array_rand($pierreMiniggioRendererProjects)
        ];

        $storyPoster = new InstagramStoryPoster();
        $uploaderProjects = $config['uploaderProjects'];
        $uploaderProject = $uploaderProjects[array_rand($pierreMiniggioRendererProjects)];

        foreach ($channels as $channel) {
            $actionUploaderAccountName = $channel['action_uploader_account_name'];
            echo PHP_EOL . PHP_EOL . 'Checking channel ' . $actionUploaderAccountName . '...';

            $instagramChannelId = $channel['i_id'];

            $storiesToPost = $nonUploadedVideoRepository->findByInstagramAndYoutubeChannelIds(
                $instagramChannelId,
                $channel['y_id']
            );

            echo PHP_EOL . count($storiesToPost) . ' stor(y/ies) to post :' . PHP_EOL;

            foreach ($storiesToPost as $storyToPost) {
                echo PHP_EOL . 'Rendering ' . $storyToPost['title'] . ' ...';

                $youtubeId = $storyToPost['id'];

                if ($actionUploaderAccountName === $pierreMiniggioInstagramActionUploaderAccountName) {
                    try {
                        $temporaryVideoFilePath = $actionRenderer->render(
                            $pierreMiniggioRendererProject['token'],
                            $pierreMiniggioRendererProject['account'],
                            $pierreMiniggioRendererProject['project'],
                            180,
                            3,
                            [
                                'typeId' => (string) rand(1, 4),
                                'thumbnail' => 'https://www.stored-youtube-video-thumbnails.ggio.fr/' . $youtubeId
                            ]
                        );
                    } catch (GithubActionRemotionRendererException $e) {
                        echo PHP_EOL . 'Error while rendering ! ' . $e->getMessage();
                        break;
                    }

                    $storyFileName = $youtubeId . '.mp4';

                    $videoFilePath = $cacheDir . $storyFileName;
                    rename($temporaryVideoFilePath, $videoFilePath);
                } else {
                    echo ' Error : No renderer for ' . $actionUploaderAccountName;

                    break;
                }

                $storyVideoUrl = $cacheUrl . '/' . $videoFilePath;

                echo ' Rendered !';

                echo PHP_EOL . 'Uploading ' . $storyToPost['title'] . ' ...';

                try {
                    $storyId = $storyPoster->upload(
                        $uploaderProject['token'],
                        $uploaderProject['account'],
                        $uploaderProject['project'],
                        30,
                        3,
                        [
                            'account' => $actionUploaderAccountName,
                            'video_url' => $storyVideoUrl,
                            'proxy' => $config['proxy']
                        ]
                    );
                } catch (InstagramStoryPosterException $e) {
                    echo PHP_EOL . 'Error while uploading ! ' . $e->getMessage();
                    break;
                }

                unlink($videoFilePath);

                $storyToPostRepository->insertStoryIfNeeded($storyId, $instagramChannelId, $youtubeId);

                echo ' Uploaded !';
            }

            echo PHP_EOL . PHP_EOL . 'Done for channel ' . $actionUploaderAccountName . ' !';
        }

        return 0;
    }
}
