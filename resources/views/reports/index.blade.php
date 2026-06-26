@extends('layouts.app')

@section('content')
<section class="page-header report-page-header">
    <h1>Laporan Keuangan</h1>
    <p>Analisis pendapatan dan laporan keuangan laundry</p>
</section>

@php
    $maxIncome = collect($rows)->max('income') ?: 1;
@endphp

<form method="GET" action="{{ route('reports.index') }}" class="report-filter-card">
    <div class="report-period-group">
        <label>Periode Laporan</label>

        <div class="report-tabs">
            <button type="submit" name="period" value="daily" class="{{ $period === 'daily' ? 'active' : '' }}">
                Hari Ini
            </button>

            <button type="submit" name="period" value="weekly" class="{{ $period === 'weekly' ? 'active' : '' }}">
                Minggu Ini
            </button>

            <button type="submit" name="period" value="monthly" class="{{ $period === 'monthly' ? 'active' : '' }}">
                Bulan Ini
            </button>
        </div>
    </div>

    <div class="report-date-group">
        <div>
            <label>Dari Tanggal</label>
            <input type="date" name="from_date" value="{{ request('from_date', $startDate->format('Y-m-d')) }}">
        </div>

        <div>
            <label>Sampai Tanggal</label>
            <input type="date" name="to_date" value="{{ request('to_date', $endDate->format('Y-m-d')) }}">
        </div>

        <button type="submit" class="report-filter-btn">Terapkan</button>
    </div>
</form>

<div class="report-income-card">
    <div>
        <p>Total Pendapatan Bersih</p>
        <h2>Rp {{ number_format($totalIncome, 0, ',', '.') }}</h2>
        <span>Periode: {{ $periodLabel }}</span>
    </div>

   
</div>

<div class="report-chart-card">
    <h3>
        Grafik Pendapatan
        {{ $period === 'daily' ? 'Harian' : ($period === 'weekly' ? 'Mingguan' : 'Bulanan') }}
    </h3>

    <div class="report-chart">
        @foreach($rows as $row)
            @php
                $height = $row['income'] > 0
                    ? max(8, ($row['income'] / $maxIncome) * 100)
                    : 0;
            @endphp

            <div class="report-bar-item">
                <div class="report-bar-track">
                    <div class="report-bar" style="height: {{ $height }}%;"></div>
                </div>

                <span>{{ $row['label'] }}</span>
            </div>
        @endforeach
    </div>
</div>

<div class="report-detail-card">
    <h3>Rincian Pendapatan</h3>

    <table class="report-table">
        <thead>
            <tr>
                <th>Periode</th>
                <th>Pendapatan</th>
            </tr>
        </thead>

        <tbody>
            @forelse($rows as $row)
                <tr>
                    <td>{{ $row['label'] }}</td>
                    <td>Rp {{ number_format($row['income'], 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="2" class="empty-row">Belum ada data pendapatan.</td>
                </tr>
            @endforelse

            <tr class="report-total-row">
                <td>Total</td>
                <td>Rp {{ number_format($totalIncome, 0, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>
</div>
@endsection
