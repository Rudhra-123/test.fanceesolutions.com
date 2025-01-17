<?php

namespace App\Services;

use MuxPhp\Api\AssetsApi;
use MuxPhp\Api\PlaybackIDApi;
use MuxPhp\Api\UploadsApi;
use MuxPhp\Configuration;
use MuxPhp\Models\CreateAssetRequest;
use MuxPhp\Models\CreateUploadRequest;
use GuzzleHttp\Client;
use Exception;

class MuxService
{
    protected $client;
    protected $baseUrl = 'https://api.mux.com/video/v1/uploads';

    public function __construct()
    {
        $this->client = new Client();
    }

    /**
     * Create a direct upload URL for uploading videos.
     *
     * @return string
     * @throws Exception
     */
    public function createDirectUpload(): string
    {
        try {
            // Request to Mux API to create upload URL
            $response = $this->client->post($this->baseUrl, [
                'auth' => [config('mux.access_token'), config('mux.secret_key')],
                'json' => [
                    'new_asset_settings' => [
                        'playback_policy' => ['public'],
                    ],
                    'cors_origin' => '*',  // Adjust if using a specific domain
                ]
            ]);

            // Parse the response and get the upload URL
            $data = json_decode($response->getBody()->getContents(), true);
            return $data['data']['url'];  // Extract and return the direct upload URL
        } catch (Exception $e) {
            throw new Exception("Error creating direct upload URL: " . $e->getMessage());
        }
    }

    /**
     * Upload a video file to Mux using the direct upload URL.
     *
     * @param string $filePath
     * @return string
     * @throws Exception
     */
    public function uploadVideoToMux(string $filePath): string
    {
        $directUploadUrl = $this->createDirectUpload();

        try {
            // Upload video to Mux directly via the PUT request
            $response = $this->client->request('PUT', $directUploadUrl, [
                'body' => fopen($filePath, 'r'), // Open the file for upload
            ]);

            // Check the HTTP status code for success
            if ($response->getStatusCode() === 200) {
                return $directUploadUrl; // Return the upload URL as confirmation
            }

            throw new Exception("Failed to upload video: HTTP " . $response->getStatusCode());
        } catch (Exception $e) {
            throw new Exception("Error uploading video: " . $e->getMessage());
        }
    }
}
