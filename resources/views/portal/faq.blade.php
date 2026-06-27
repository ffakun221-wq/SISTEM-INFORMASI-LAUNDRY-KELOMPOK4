@extends('layouts.portal')

@section('content')
<section class="page-header portal-page-header">
    <h1>Frequently Asked Questions</h1>
    <p>Daftar berisi kumpulan pertanyaan yang paling umum ditanyakan</p>
</section>

<div class="faq-card">
    <form>
        <div class="faq-list">
            <div class="faq-item">
                <div>
                    <label>Kapan jadwal operasional reguler toko?</label>
                    <p>Kami buka setiap hari Senin - Sabtu pukul 08:00 - 18:00 WIB</p>
                </div>
            </div>
            <div class="faq-item">
                <div>
                    <label>Bagaimana cara cek Status Cucian?</label>
                    <p>Buka halaman Pesanan Aktif, di halaman tersebut bisa terlihat status cucian dan bayar</p>
                </div>
            </div>
            <div class="faq-item">
                <div>
                    <label>Bagaimana cara Request Jemput?</label>
                    <p>Buka halaman Buat Pesanan, isi form pada halaman tersebut, setelah submit Kasir akan mengkonfirmasi request jemputan</p>
                </div>
            </div>
            <div class="faq-item">
                <div>
                    <label>Bagaimana cara Request Antar?</label>
                    <p>Buka halaman Pesanan Aktif, buka detail order yang ingin di antar, jika order memenuhi untuk request antar, akan ada form yang bisa diisi, setelah submit kasir akan mengkonfirmasi request antar</p>
                </div>
            </div>
            <div class="faq-item">
                <div>
                    <label>Bagaimana cara menghubungi via WhatsApp?</label>
                    <p>Kami bisa dihubungi menggunakan nomor 08123456789</p>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection
