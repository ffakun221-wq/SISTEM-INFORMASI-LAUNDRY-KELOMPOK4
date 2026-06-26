@extends('layouts.auth')

@section('content')
<div class="auth-card">
    <div class="logo-box">L</div>

    <h1 class="auth-title">Sistem Laundry</h1>
    <p class="auth-subtitle">Masuk sebagai admin atau kasir</p>

    @if(session('info'))
        <div style="margin-bottom: 14px; padding: 10px; border-radius: 8px; background: #e0f2fe; color: #075985; font-size: 14px;">
            {{ session('info') }}
        </div>
    @endif

    @if($errors->any())
        <div style="margin-bottom: 14px; padding: 10px; border-radius: 8px; background: #fee2e2; color: #991b1b; font-size: 14px;">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('login.process') }}">
        @csrf

        <div class="form-group">
            <label>Username</label>
            <input type="text" name="username" class="form-control" placeholder="Masukkan username" value="{{ old('username') }}" required>
        </div>

        <div class="form-group">
            <label>Kata Sandi</label>
            <input type="password" name="password" class="form-control" placeholder="Masukkan kata sandi" required>
        </div>

        <button type="submit" class="btn btn-primary">Masuk</button>

        <a href="{{ route('status.check') }}" class="btn btn-outline">Cek Status Cucian</a>
    </form>
</div>
@endsection
