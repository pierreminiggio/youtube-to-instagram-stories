<?php

namespace PierreMiniggio\YoutubeToInstagramStories\Repository;

use PierreMiniggio\DatabaseConnection\DatabaseConnection;

class StoryToPostRepository
{
    public function __construct(private DatabaseConnection $connection)
    {}

    public function insertStoryIfNeeded(
        string $instagramId,
        int $instagramChannelId,
        int $youtubeVideoId
    ): void
    {
        $this->connection->start();
        $videoQueryParams = [
            'channel_id' => $instagramChannelId,
            'instagram_id' => $instagramId
        ];
        $findVideoIdQuery = ['
            SELECT id FROM instagram_story
            WHERE channel_id = :channel_id
            AND instagram_id = :instagram_id
            ;
        ', $videoQueryParams];
        $queriedIds = $this->connection->query(...$findVideoIdQuery);
        
        if (! $queriedIds) {
            $this->connection->exec('
                INSERT INTO instagram_story (channel_id, instagram_id)
                VALUES (:channel_id, :instagram_id)
                ;
            ', $videoQueryParams);
            $queriedIds = $this->connection->query(...$findVideoIdQuery);
        }

        $postId = (int) $queriedIds[0]['id'];
        
        $pivotQueryParams = [
            'instagram_id' => $postId,
            'youtube_id' => $youtubeVideoId
        ];

        $queriedPivotIds = $this->connection->query('
            SELECT id FROM instagram_story_youtube_video
            WHERE instagram_id = :instagram_id
            AND youtube_id = :youtube_id
            ;
        ', $pivotQueryParams);
        
        if (! $queriedPivotIds) {
            $this->connection->exec('
                INSERT INTO instagram_story_youtube_video (instagram_id, youtube_id)
                VALUES (:instagram_id, :youtube_id)
                ;
            ', $pivotQueryParams);
        }

        $this->connection->stop();
    }
}
