<?php

namespace App\Services;

use GuzzleHttp\Client;
use Exception;

class MuxService
{
    protected $client;
    protected $baseUrl = 'https://api.mux.com/video/v1';

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
    public function createDirectUpload(): array
    {
        try {
            $response = $this->client->post("{$this->baseUrl}/uploads", [
                'auth' => [config('mux.access_token'), config('mux.secret_key')],
                'json' => [
                    'new_asset_settings' => [
                        'playback_policy' => ['public'],
                    ],
                    'cors_origin' => '*', // Adjust to your domain
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            // $uploadId=$data['data']['id'];
            // dd($uploadId);

            \Log::info('Mux Response:', $data);

            return $data; // Return the upload URL
        } catch (Exception $e) {
            \Log::error('Mux Upload Error: ' . $e->getMessage());
            throw new Exception("Error creating direct upload: " . $e->getMessage());
        }
    }

    /**
     * Upload a video file to Mux using the direct upload URL.
     *
     * @param string $filePath
     * @return array
     * @throws Exception
     */
    public function uploadVideoToMux(string $filePath): array
    {
        // Step 1: Get the direct upload URL
        $directUploadUrl = $this->createDirectUpload();

        try {
            // Step 2: Upload the video file using the PUT request
            $uploadResponse = $this->client->request('PUT', $directUploadUrl, [
                'body' => fopen($filePath, 'r'), // Open the file for upload
            ]);

            // Step 3: Wait for the upload to complete and fetch the asset ID from the response
            return $this->getAssetDetailsFromUploadUrl($directUploadUrl);
        } catch (Exception $e) {
            \Log::error('Video Upload Error: ' . $e->getMessage());
            throw new Exception("Error uploading video: " . $e->getMessage());
        }
    }

    /**
     * Fetch the Asset ID and Playback ID for a video uploaded via direct upload URL.
     *
     * @param string $uploadUrl
     * @return array
     * @throws Exception
     */
    public function getAssetDetailsFromUploadId(string $uploadId): array
    {
        try {
            // Extend the maximum execution time for this script
            set_time_limit(60); // Allow up to 5 minutes
            
            $pollInterval = 3; // Poll every 3 seconds
            $timeout = 60;    // Maximum time to wait (5 minutes)
            $elapsedTime = 0;
    
            // Poll for upload completion
            $uploadDetails = $this->getUploadDetails($uploadId);
            $status = $uploadDetails['data']['status'] ?? null;
            //dd($uploadDetails);
    
            // while ($status !== 'finished') {
            //     if ($elapsedTime >= $timeout) {
            //         throw new Exception("Polling timed out after {$timeout} seconds.");
            //     }
    
            //     sleep($pollInterval); // Wait before checking again
            //     $elapsedTime += $pollInterval;
    
            //     $uploadDetails = $this->getUploadDetails($uploadId);
            //     $status = $uploadDetails['data']['status'] ?? null;
    
            //     // Optionally log status for debugging purposes
            //     \Log::info('Upload status', ['status' => $status, 'elapsed_time' => $elapsedTime]);
            // }
    
            // Extract the Asset ID
            $assetId = $uploadDetails['data']['asset_id'] ?? null;
            //dd($assetId);
    
            if (!$assetId) {
                throw new Exception("Asset ID not found in upload details.");
            }
    
            // Fetch Playback ID using the Asset ID
            $playbackId = $this->getPlaybackId($assetId);
            //dd($playbackId);
    
            if (!$playbackId) {
                throw new Exception("Playback ID not found for Asset ID: {$assetId}");
            }
    
            return [
                'asset_id' => $assetId,
                'playback_id' => $playbackId,
            ];
        } catch (Exception $e) {
            \Log::error('Error fetching asset details: ' . $e->getMessage());
            throw new Exception("Error fetching asset details: " . $e->getMessage());
        }
    }
    
    
    /**
     * Fetch the Playback ID for a given Asset ID.
     *
     * @param string $assetId
     * @return string
     * @throws Exception
     */
    public function getPlaybackId(string $assetId): string
    {
        try {
            // Fetch asset details from Mux API
            $response = $this->client->get("{$this->baseUrl}/assets/{$assetId}", [
                'auth' => [config('mux.access_token'), config('mux.secret_key')],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            // Extract the Playback ID
            $playbackIds = $data['data']['playback_ids'] ?? [];
            if (empty($playbackIds)) {
                throw new Exception("No playback IDs found for the asset.");
            }

            return $playbackIds[0]['id']; // Return the first playback ID
        } catch (Exception $e) {
            \Log::error('Error fetching playback ID: ' . $e->getMessage());
            throw new Exception("Error fetching playback ID: " . $e->getMessage());
        }
    }

    /**
     * Fetch upload details for a given upload ID.
     *
     * @param string $uploadId
     * @return array
     * @throws Exception
     */
    public function getUploadDetails(string $uploadId): array
    {
        try {
            $response = $this->client->get("{$this->baseUrl}/uploads/{$uploadId}", [
                'auth' => [config('mux.access_token'), config('mux.secret_key')],
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (Exception $e) {
            \Log::error("Error fetching upload details: " . $e->getMessage());
            throw new Exception("Error fetching upload details: " . $e->getMessage());
        }
    }



}
