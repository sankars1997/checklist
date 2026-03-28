<style>
    .news-flash-body {
    font-size: 14px;
    color:rgb(105, 72, 0);
    font-family: 'Verdana', Geneva, sans-serif;
    letter-spacing: 0.5px;
}

.news-item {
    line-height: 1.6;
}

.card-header {
    font-size: 16px;
}

</style>
<div class="container my-3">
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm border-danger">
                <div class="card-header bg-danger text-white fw-semibold">
                    News Flash
                </div>
                <div class="card-body news-flash-body">
                    {{-- Optional Marquee (disabled by default) --}}
                    {{-- <marquee scrollamount="3" direction="up" behavior="scroll"> --}}
                    
                    @foreach ($finalMessages as $message)
                        <div class="news-item d-flex align-items-start mb-2">
                            
                            <div class="news-text">
                                {!! $message['msg'] !!}
                                @if ($message['new'])
                                    <sup>
                                        <img src="{{ asset('assets/img/new.gif') }}" alt="new" style="height:12px;">
                                    </sup>
                                @endif
                            </div>
                        </div>
                    @endforeach

                    {{-- </marquee> --}}
                </div>
            </div>
        </div>
    </div>
</div>