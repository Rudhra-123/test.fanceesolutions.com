<?php

namespace App\Services;

use MuxPhp\Api\AssetsApi;
use MuxPhp\Api\PlaybackIDApi;
use MuxPhp\Configuration;
use MuxPhp\Models\CreateAssetRequest;
use Exception;

class MuxService
{
    protected $assetsApi;
    protected $playbackIDApi;

    public function __construct()
    {
        $config = Configuration::getDefaultConfiguration()
            ->setUsername(config('mux.access_token'))
            ->setPassword(config('mux.secret_key'));

        $this->assetsApi = new AssetsApi(null, $config);
        $this->playbackIDApi = new PlaybackIDApi(null, $config);
    }

    /**
     * Upload a video to Mux.
     *
     * @param string $videoPath
     * @return string
     */
    public function uploadVideo(string $videoPath): string
    {
        try {
            $upload = new CreateAssetRequest([
                'input' => $videoPath,
                'playback_policy' => ['public'],
            ]);

            $response = $this->assetsApi->createAsset($upload);
            return $response->getData()->getId();
        } catch (Exception $e) {
            throw new Exception("Error uploading video: " . $e->getMessage());
        }
    }

    /**
     * Get video playback URL.
     *
     * @param string $assetId
     * @return string|null
     */
    public function getPlaybackUrl(string $assetId): ?string
    {
        try {
            $playbackIds = $this->assetsApi->getAsset($assetId)->getData()->getPlaybackIds();
            if (!empty($playbackIds)) {
                return 'https://stream.mux.com/' . $playbackIds[0]->getId() . '.m3u8';
            }
            return null;
        } catch (Exception $e) {
            throw new Exception("Error fetching playback URL: " . $e->getMessage());
        }
    }
}
