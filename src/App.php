<?php

namespace PierreMiniggio\YoutubeToInstagramStories;

use PierreMiniggio\DatabaseFetcher\DatabaseFetcher;
use PierreMiniggio\GithubActionRemotionRenderer\GithubActionRemotionRenderer;
use PierreMiniggio\GithubActionRemotionRenderer\GithubActionRemotionRendererException;
use PierreMiniggio\InstagramStoryPoster\InstagramStoryPoster;
use PierreMiniggio\InstagramStoryPoster\InstagramStoryPosterException;
use PierreMiniggio\YoutubeToInstagramStories\Connection\DatabaseConnectionFactory;
use PierreMiniggio\YoutubeToInstagramStories\Repository\LinkedChannelRepository;
use PierreMiniggio\YoutubeToInstagramStories\Repository\NonUploadedStoryRepository;
use PierreMiniggio\YoutubeToInstagramStories\Repository\StoryToPostRepository;

class App
{
    public const PIERREMINIGGIO = 'PIERREMINIGGIO';
    public const ELON = 'ELON';

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
        $nonUploadedStoryRepository = new NonUploadedStoryRepository($databaseConnection);
        $storyToPostRepository = new StoryToPostRepository($databaseConnection);

        $channels = $channelRepository->findAll();

        $actionRenderer = new GithubActionRemotionRenderer();

        $pierreMiniggioInstagramActionUploaderAccountName = self::PIERREMINIGGIO;
        $pierreMiniggioRendererProjects = $config['pierreMiniggioRendererProjects'];
        $pierreMiniggioRendererProject = $pierreMiniggioRendererProjects[
            array_rand($pierreMiniggioRendererProjects)
        ];

        $elonInstagramActionUploaderAccountName = self::ELON;
        $elonRendererProjects = $config['elonRendererProjects'];
        $elonRendererProject = $elonRendererProjects[array_rand($elonRendererProjects)];

        $storyPoster = new InstagramStoryPoster();
        $uploaderProjects = $config['uploaderProjects'];
        $uploaderProject = $uploaderProjects[array_rand($pierreMiniggioRendererProjects)];

        foreach ($channels as $channel) {
            $actionUploaderAccountName = $channel['action_uploader_account_name'];
            echo PHP_EOL . PHP_EOL . 'Checking channel ' . $actionUploaderAccountName . '...';

            $instagramChannelId = $channel['i_id'];

            $storiesToPost = $nonUploadedStoryRepository->findByInstagramAndYoutubeChannelIdsAndUploaderAccountName(
                $instagramChannelId,
                $channel['y_id'],
                $actionUploaderAccountName
            );

            echo PHP_EOL . count($storiesToPost) . ' stor(y/ies) to post :' . PHP_EOL;

            foreach ($storiesToPost as $storyToPost) {
                $title = $storyToPost['title'];
                echo PHP_EOL . 'Rendering ' . $title . ' ...';

                $youtubeId = $storyToPost['id'];

                $storyFileName = $youtubeId . '.mp4';
                $videoFilePath = $cacheDir . $storyFileName;

                if (! file_exists($videoFilePath)) {
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

                        rename($temporaryVideoFilePath, $videoFilePath);
                    } elseif ($actionUploaderAccountName === $elonInstagramActionUploaderAccountName) {
                        try {
                            $temporaryVideoFilePath = $actionRenderer->render(
                                $elonRendererProject['token'],
                                $elonRendererProject['account'],
                                $elonRendererProject['project'],
                                180,
                                3,
                                [
                                    'thumbnail' => 'https://www.stored-youtube-video-thumbnails.ggio.fr/' . $youtubeId,
                                    'title' => $title,
                                ]
                            );
                        } catch (GithubActionRemotionRendererException $e) {
                            echo PHP_EOL . 'Error while rendering ! ' . $e->getMessage();
                            break;
                        }

                        rename($temporaryVideoFilePath, $videoFilePath);
                    } else {
                        echo ' Error : No renderer for ' . $actionUploaderAccountName;
                        break;
                    }
                    echo ' Rendered !';
                } else {
                    echo ' Already rendered !';
                }

                $storyVideoUrl = $cacheUrl . '/' . $storyFileName;

                echo PHP_EOL . 'Uploading ' . $title . ' ...';

                try {
                    $storyIds = $storyPoster->upload(
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
                    unlink($videoFilePath);
                    echo PHP_EOL . 'Error while uploading ! ' . $e->getMessage();
                    break;
                }

                unlink($videoFilePath);

                foreach ($storyIds as $storyId) {
                    $storyToPostRepository->insertStoryIfNeeded($storyId, $instagramChannelId, $youtubeId);
                }

                echo ' Uploaded !';
            }

            echo PHP_EOL . PHP_EOL . 'Done for channel ' . $actionUploaderAccountName . ' !';
        }

        return 0;
    }
}
