<?php

test('the faq page replaces cara belanja and shows preloved questions', function () {
    $this->get(route('faq'))
        ->assertSuccessful()
        ->assertSee('FAQ')
        ->assertSee('Barangnya baru atau preloved?')
        ->assertSee('preloved')
        ->assertSee('Cara order & bayarnya gimana?')
        ->assertSee('Bisa tukar atau retur ga?')
        ->assertSee('Langsung chat WA aja');
});

test('the old cara belanja url redirects to faq', function () {
    $this->get('/cara-belanja')
        ->assertRedirect('/faq');
});

test('the about page explains preloved focus in a casual tone', function () {
    $this->get(route('about'))
        ->assertSuccessful()
        ->assertSee('Tentang Kami')
        ->assertSee('preloved')
        ->assertSee('barang baru')
        ->assertSee('Preloved pilihan');
});
