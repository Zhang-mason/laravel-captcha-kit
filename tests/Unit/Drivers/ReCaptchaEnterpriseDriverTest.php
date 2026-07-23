<?php

use Google\ApiCore\Testing\MockStatus;
use Google\Cloud\RecaptchaEnterprise\V1\Assessment;
use Google\Cloud\RecaptchaEnterprise\V1\CreateAssessmentRequest;
use Google\Cloud\RecaptchaEnterprise\V1\RiskAnalysis;
use Google\Cloud\RecaptchaEnterprise\V1\TokenProperties;
use Google\Cloud\RecaptchaEnterprise\V1\TokenProperties\InvalidReason;
use Google\Protobuf\Timestamp;
use Google\Rpc\Code;
use Mason\Captcha\Facades\Captcha;

function enterpriseAssessment(array $tokenProperties = [], ?float $score = null): Assessment
{
    $assessment = new Assessment([
        'token_properties' => new TokenProperties($tokenProperties),
    ]);

    if ($score !== null) {
        $assessment->setRiskAnalysis(new RiskAnalysis(['score' => $score]));
    }

    return $assessment;
}

it('maps a valid assessment', function () {
    $transport = $this->fakeEnterpriseTransport();
    $transport->addResponse(enterpriseAssessment([
        'valid' => true,
        'action' => 'login',
        'hostname' => 'example.test',
        'create_time' => new Timestamp(['seconds' => 1783468800]),
    ], 0.9));

    $result = Captcha::driver('recaptcha_enterprise')->verify('the-token', '1.2.3.4');

    expect($result->passed())->toBeTrue()
        // Protobuf floats are 32-bit, so the score comes back with precision noise.
        ->and($result->score)->toEqualWithDelta(0.9, 1e-6)
        ->and($result->action)->toBe('login')
        ->and($result->hostname)->toBe('example.test')
        ->and($result->challengedAt?->toIso8601ZuluString())->toBe('2026-07-08T00:00:00Z')
        ->and($result->raw['tokenProperties']['valid'])->toBeTrue();
});

it('sends the project parent and event fields', function () {
    $transport = $this->fakeEnterpriseTransport();
    $transport->addResponse(enterpriseAssessment(['valid' => true]));

    Captcha::driver('recaptcha_enterprise')->expectAction('login')->verify('the-token', '1.2.3.4');

    /** @var CreateAssessmentRequest $request */
    $request = $transport->popReceivedCalls()[0]->getRequestObject();
    $event = $request->getAssessment()->getEvent();

    expect($request->getParent())->toBe('projects/test-project')
        ->and($event->getToken())->toBe('the-token')
        ->and($event->getSiteKey())->toBe('test-site-key')
        ->and($event->getExpectedAction())->toBe('login')
        ->and($event->getUserIpAddress())->toBe('1.2.3.4');
});

it('fails passed() below the score threshold while success stays true', function () {
    config(['captcha.drivers.recaptcha_enterprise.threshold' => 0.5]);
    $transport = $this->fakeEnterpriseTransport();
    $transport->addResponse(enterpriseAssessment(['valid' => true], 0.3));

    $result = Captcha::driver('recaptcha_enterprise')->verify('the-token');

    expect($result->success)->toBeTrue()
        ->and($result->failed())->toBeTrue();
});

it('maps an invalid token to its invalid reason', function () {
    $transport = $this->fakeEnterpriseTransport();
    $transport->addResponse(enterpriseAssessment([
        'valid' => false,
        'invalid_reason' => InvalidReason::EXPIRED,
    ]));

    $result = Captcha::driver('recaptcha_enterprise')->verify('stale-token');

    expect($result->failed())->toBeTrue()
        ->and($result->errorCodes)->toBe(['expired']);
});

it('rejects an action mismatch locally', function () {
    $transport = $this->fakeEnterpriseTransport();
    $transport->addResponse(enterpriseAssessment(['valid' => true, 'action' => 'register'], 0.9));

    $result = Captcha::driver('recaptcha_enterprise')->expectAction('login')->verify('the-token');

    expect($result->failed())->toBeTrue()
        ->and($result->errorCodes)->toBe(['action-mismatch']);
});

it('clears the expected action after each verification', function () {
    $transport = $this->fakeEnterpriseTransport();
    $transport->addResponse(enterpriseAssessment(['valid' => true, 'action' => 'register'], 0.9));
    $transport->addResponse(enterpriseAssessment(['valid' => true, 'action' => 'register'], 0.9));

    $driver = Captcha::driver('recaptcha_enterprise');

    expect($driver->expectAction('login')->verify('the-token')->failed())->toBeTrue()
        ->and($driver->verify('the-token')->passed())->toBeTrue();
});

it('falls back to the configured action', function () {
    config(['captcha.drivers.recaptcha_enterprise.action' => 'login']);
    $transport = $this->fakeEnterpriseTransport();
    $transport->addResponse(enterpriseAssessment(['valid' => true, 'action' => 'register'], 0.9));

    $result = Captcha::driver('recaptcha_enterprise')->verify('the-token');

    expect($result->failed())->toBeTrue()
        ->and($result->errorCodes)->toBe(['action-mismatch']);
});

it('fails closed when the assessment call errors', function () {
    $transport = $this->fakeEnterpriseTransport();
    $transport->addResponse(null, new MockStatus(Code::UNAVAILABLE, 'unavailable'));

    $result = Captcha::driver('recaptcha_enterprise')->verify('the-token');

    expect($result->failed())->toBeTrue()
        ->and($result->errorCodes)->toBe(['transport-error']);
});

it('can fail open when configured', function () {
    config(['captcha.on_failure' => 'pass']);
    $transport = $this->fakeEnterpriseTransport();
    $transport->addResponse(null, new MockStatus(Code::UNAVAILABLE, 'unavailable'));

    $result = Captcha::driver('recaptcha_enterprise')->verify('the-token');

    expect($result->passed())->toBeTrue()
        ->and($result->errorCodes)->toBe(['transport-error']);
});

it('exposes frontend metadata', function () {
    $this->fakeEnterpriseTransport();

    $driver = Captcha::driver('recaptcha_enterprise');

    expect($driver->responseFieldName())->toBe('g-recaptcha-response')
        ->and($driver->scriptUrl())->toBe('https://www.google.com/recaptcha/enterprise.js?render=test-site-key')
        ->and($driver->siteKey())->toBe('test-site-key');
});

it('drops the render parameter in checkbox mode', function () {
    config(['captcha.drivers.recaptcha_enterprise.mode' => 'checkbox']);
    $this->fakeEnterpriseTransport();

    expect(Captcha::driver('recaptcha_enterprise')->scriptUrl())
        ->toBe('https://www.google.com/recaptcha/enterprise.js');
});
