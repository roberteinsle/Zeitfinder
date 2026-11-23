@if (session('status'))
    <div class="alert-success mb-6" role="status">{{ session('status') }}</div>
@endif
@if ($errors->has('poll'))
    <div class="alert-error mb-6" role="alert">{{ $errors->first('poll') }}</div>
@endif
