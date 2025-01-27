@php
    use Illuminate\Support\Facades\DB;

    $data = $order->id; // Replace $order->id with your actual order ID variable

    // Initialize variables
    $playbackId = null;

    // Query the mux_videos table for the latest record with the given order_id
    $muxUriRecord = DB::table('mux_videos')->where('order_id', $data)->latest('created_at')->first();

    if ($muxUriRecord) {
        $playbackId = $muxUriRecord->uri; // Fetch the playback ID from the database
    }
@endphp

<script src="https://cdn.jsdelivr.net/npm/@mux/mux-player"></script>
@if ($playbackId)
    <mux-player
      playback-id="{{ $playbackId }}" <!-- Use the dynamic playback ID -->
      metadata-video-title="Video for Order ID {{ $data }}" <!-- Optional metadata -->
      metadata-viewer-user-id="User_{{ auth()->id() }}" <!-- Optional metadata -->
      primary-color="#ffffff"
      secondary-color="#000000"
      accent-color="#fa50b5"
    ></mux-player>
@else
    <p>No video available for this order.</p>
@endif
