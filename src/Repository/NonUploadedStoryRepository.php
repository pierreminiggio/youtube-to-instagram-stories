<?php

namespace PierreMiniggio\YoutubeToInstagramStories\Repository;

use PierreMiniggio\DatabaseConnection\DatabaseConnection;
use PierreMiniggio\YoutubeToInstagramStories\App;

class NonUploadedStoryRepository
{
    public function __construct(private DatabaseConnection $connection)
    {}

    public function findByInstagramAndYoutubeChannelIdsAndUploaderAccountName(
        int $instagramChannelId,
        int $youtubeChannelId,
        string $actionUploaderAccountName
    ): array
    {
        $this->connection->start();

        $postedInstagramStoryIds = $this->connection->query('
            SELECT i.id
            FROM instagram_story as i
            RIGHT JOIN instagram_story_youtube_video as isyv
            ON i.id = isyv.instagram_id
            WHERE i.channel_id = :channel_id
        ', ['channel_id' => $instagramChannelId]);
        $postedInstagramStoryIds = array_map(fn ($entry) => (int) $entry['id'], $postedInstagramStoryIds);

        $isElonChannel = $actionUploaderAccountName === App::ELON;
        if ($isElonChannel) {
            // Elon Musk Addict' Shorts are posted from TikTok to Youtube and to Instagram Stories, so we obviously
            // don't a notification saying that you should check out the new posted Short on Youtube, when it's
            // already reposted on Instagram...
            // So yeah, that's why I exclude them
            $shortsIds = $this->connection->query(<<<SQL
                SELECT id FROM youtube_video
                WHERE channel_id = :channel_id
                AND description like '%#Shorts%'
            SQL, ['channel_id' => $youtubeChannelId]);
            $shortsIds = array_map(fn ($entry) => (int) $entry['id'], $shortsIds);
        }

        $videosToUpload = $this->connection->query('
            SELECT
                y.id,
                y.title,
                y.thumbnail
            FROM youtube_video as y
            ' . (
                $postedInstagramStoryIds
                    ? 'LEFT JOIN instagram_story_youtube_video as isyv
                    ON y.id = isyv.youtube_id
                    AND isyv.instagram_id IN (' . implode(', ', $postedInstagramStoryIds) . ')'
                    : ''
            ) . '
            LEFT JOIN youtube_video_unpostable_on_instagram_stories as yvuoi
            ON yvuoi.youtube_id = y.id
            
            WHERE y.channel_id = :channel_id
            AND yvuoi.id IS NULL
            ' . ($postedInstagramStoryIds ? 'AND isyv.id IS NULL' : '') . '
            ' . (
                $isElonChannel && $shortsIds
                    ? (' AND y.id NOT IN (' . implode(', ', $shortsIds) . ')')
                    : ''
            ) . '
            LIMIT 1
            ;
        ', [
            'channel_id' => $youtubeChannelId
        ]);
        $this->connection->stop();

        return $videosToUpload;
    }
}
