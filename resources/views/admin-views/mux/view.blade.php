<div class="card">
    <div class="card-body">
        <h4>Upload Video to Mux</h4>
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif
        <form action="{{ route('mux.upload') }}" method="post" enctype="multipart/form-data">
            @csrf

            <div class="form-group">
                <label for="order_id">Order ID</label>
                <input 
                    type="text" 
                    name="order_id" 
                    class="form-control" 
                    id="order_id" 
                    value="{{ $order['id'] }}" 
                    required
                >

                <label for="video">Video File</label>
                <input 
                    type="file" 
                    name="video" 
                    class="form-control" 
                    id="video" 
                    required
                >
            </div>
            <div class="d-flex justify-content-end">
                <button type="submit" class="btn btn-primary">Upload Video</button>
            </div>
        </form>
    </div>
</div>
