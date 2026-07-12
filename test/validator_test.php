<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../pemira_validator.php';

class validator_test extends TestCase {
    
    public function testValidasiAksesSudahVote() {
        $validator = new \App\PemiraValidator();
        $hasilNyata = $validator->validasiAksesBilik(1, 'sedang_diproses');
        $this->assertEquals("Anda sudah melakukan voting!", $hasilNyata);
    }

    public function testValidasiAksesBilikTutup() {
        $validator = new \App\PemiraValidator();
        $hasilNyata = $validator->validasiAksesBilik(0, 'selesai');
        $this->assertEquals("Anda belum terdaftar di bilik suara. Silakan hubungi panitia.", $hasilNyata);
    }

    public function testValidasiAksesSukses() {
        $validator = new \App\PemiraValidator();
        $hasilNyata = $validator->validasiAksesBilik(0, 'sedang_diproses');
        $this->assertEquals("Bilik aktif, silakan memilih", $hasilNyata);
    }
}


