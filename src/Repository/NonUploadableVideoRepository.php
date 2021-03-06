<?php

namespace PierreMiniggio\YoutubeToInstagramStories\Repository;

use PierreMiniggio\DatabaseConnection\DatabaseConnection;

class NonUploadableVideoRepository
{
    public function __construct(private DatabaseConnection $connection)
    {}

    public function markAsNonUploadableIfNeeded(int $youtubeVideoId): void
    {
        $this->connection->start();

        $videoQueryParams = ['video_id' => $youtubeVideoId];
        $queriedIds = $this->connection->query('
            SELECT id FROM youtube_video_unpostable_on_instagram_stories
            WHERE youtube_id = :video_id
        ', $videoQueryParams);
        
        if (! $queriedIds) {
            $this->connection->exec('
                INSERT INTO youtube_video_unpostable_on_instagram_stories (youtube_id)
                VALUES (:video_id)
                ;
            ', $videoQueryParams);
        }

        $this->connection->stop();
    }
}
