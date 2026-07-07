<?php

use Mason\Captcha\VerificationResult;

it('passes when successful and no threshold applies', function () {
    $result = new VerificationResult(success: true);

    expect($result->passed())->toBeTrue()
        ->and($result->failed())->toBeFalse();
});

it('fails when the provider rejected the token', function () {
    $result = new VerificationResult(success: false, errorCodes: ['invalid-input-response']);

    expect($result->passed())->toBeFalse()
        ->and($result->failed())->toBeTrue()
        ->and($result->errorCodes)->toBe(['invalid-input-response']);
});

it('applies the score threshold', function (float $score, bool $expected) {
    $result = new VerificationResult(success: true, score: $score, threshold: 0.5);

    expect($result->passed())->toBe($expected);
})->with([
    'score below threshold' => [0.3, false],
    'score at threshold' => [0.5, true],
    'score above threshold' => [0.9, true],
]);

it('ignores the threshold when the provider returned no score', function () {
    $result = new VerificationResult(success: true, threshold: 0.5);

    expect($result->passed())->toBeTrue();
});
