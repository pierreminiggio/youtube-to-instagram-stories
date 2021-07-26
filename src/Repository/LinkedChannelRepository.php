<?php

namespace PierreMiniggio\YoutubeToInstagramStories\Repository;

use PierreMiniggio\DatabaseFetcher\DatabaseFetcher;

class LinkedChannelRepository
{
    public function __construct(private DatabaseFetcher $fetcher)
    {}

    public function findAll(): array
    {
        return $this->fetcher->query(
            $this->fetcher->createQuery(
                'instagram_stories_channel_youtube_channel as iscyc'
            )->join(
                'instagram_stories_channel as i',
                'i.id = iscyc.instagram_id'
            )->select(
                'iscyc.youtube_id as y_id,',
                'i.id as i_id',
                'i.action_uploader_account_name as action_uploader_account_name'
            )
        );
    }
}
