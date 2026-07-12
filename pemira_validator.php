<?php
namespace App;

class PemiraValidator {
    public function validasiAksesBilik($hasVoted, $statusBilik) {
        if ($hasVoted == 1) {
            return "Anda sudah melakukan voting!";
        }
        
        if ($statusBilik !== 'sedang_diproses') {
            return "Anda belum terdaftar di bilik suara. Silakan hubungi panitia.";
        }
        
        return "Bilik aktif, silakan memilih";
    }
}