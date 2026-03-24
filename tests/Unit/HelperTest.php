<?php

use App\Utils\Helper;

it('generates a six digit numeric code', function () {
    $code = Helper::generateNumericCode();

    expect($code)->toMatch('/^\d{6}$/');
});

it('supports fixed-length numeric codes with zero padding', function () {
    $code = Helper::generateNumericCode(6);

    expect($code)->toHaveLength(6)
        ->and($code)->toMatch('/^\d+$/');
});