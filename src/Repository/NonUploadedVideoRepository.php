<?php

namespace PierreMiniggio\YoutubeToInstagramStories\Repository;

use PierreMiniggio\DatabaseConnection\DatabaseConnection;

class NonUploadedVideoRepository
{
    public function __construct(private DatabaseConnection $connection)
    {}

    public function findByInstagramAndYoutubeChannelIds(int $instagramChannelId, int $youtubeChannelId): array
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
            LIMIT 1
            ;
        ', [
            'channel_id' => $youtubeChannelId
        ]);
        $this->connection->stop();

        return $videosToUpload;
    }
}
