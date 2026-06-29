@extends('layouts.app')

@section('content')
<section class="page-header settings-page-header">
    <h1>Konfigurasi Sistem</h1>
    <p>Atur tarif layanan tambahan dan aturan poin loyalitas</p>
</section>

@if(session('success'))
    <div class="alert-success">{{ session('success') }}</div>
@endif

@if($errors->any())
    <div class="alert-error">
        {{ $errors->first() }}
    </div>
@endif

<div class="settings-card">
    <form method="POST" action="{{ route('settings.update') }}">
        @csrf
        @method('PUT')

        <div class="settings-list">
            @foreach($settings as $setting)
                <div class="settings-item">
                    <div>
                        <label>{{ $setting->label }}</label>
                        <p>{{ $setting->description }}</p>
                    </div>

                    <div class="settings-input-group">
                        <input
                            type="number"
                            step="0.01"
                            min="0"
                            name="settings[{{ $setting->key }}]"
                            value="{{ old('settings.' . $setting->key, $setting->value) }}"
                        >

                        @if($setting->unit)
                            <span>{{ $setting->unit }}</span>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        <div class="settings-actions">
            <button type="submit">Simpan Konfigurasi</button>
        </div>
    </form>
</div>

<div class="settingwa-card">
    <div>
        <h3>Log Notifikasi WhatsApp</h3>
        <p>Cek notifikasi WhatsApp yang terkirim saat order siap diambil</p>
    </div>

    <a href="{{ route('logwa.index') }}" class="settingwa-btn">
        Buka Halaman
    </a>
</div>
@endsection
