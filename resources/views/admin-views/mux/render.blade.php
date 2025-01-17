@php
    use App\Models\MuxUri;

    $data = $order->id;

    // Initialize variables
    $playbackUrl = null;

    // Check if the current order ID exists in the mux_videos table
    $muxUriRecord = MuxUri::where('order_id', $data)->latest('created_at')->first();

    if ($muxUriRecord) {
        $uploadId = $muxUriRecord->uri;

        // Generate the playback URL
        $playbackUrl = "https://stream.mux.com/{$uploadId}.m3u8";
    }
@endphp

<div class="video-container" style="max-width: 640px; margin: auto; text-align: center;">
    <h1>Watch this Video!</h1>
    <div class="video-and-button" style="display: flex; align-items: center; justify-content: center;">
        @if($playbackUrl)
            <!-- Display the video with the unique identifier -->
            <video width="640" height="360" controls>
                <source src="{{ $playbackUrl }}" type="application/x-mpegURL">
                Your browser does not support the video tag.
            </video>
        @else
            <p>No video found for this order.</p>
        @endif
    </div>
</div>
