<?php

declare(strict_types=1);

use App\Support\Broadcasting\PusherHttpBroadcaster;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

uses(TestCase::class);

function makeTestBroadcaster(): PusherHttpBroadcaster
{
    return new PusherHttpBroadcaster(
        appId: '123',
        key: 'test-key',
        secret: 'test-secret',
        cluster: 'ap1',
    );
}

it('CHT-T02 signs private channel auth with the Pusher socket signature', function (): void {
    $broadcaster = makeTestBroadcaster();

    $request = Request::create('/broadcasting/auth', 'POST', [
        'socket_id' => '1.1',
        'channel_name' => 'private-conversation.abc',
    ]);

    $response = $broadcaster->validAuthenticationResponse($request, true);

    expect($response)->toBe([
        'auth' => 'test-key:'.hash_hmac('sha256', '1.1:private-conversation.abc', 'test-secret'),
    ]);
});

it('NTF-T06 posts signed events to the Pusher REST endpoint', function (): void {
    Http::fake();

    makeTestBroadcaster()->broadcast(['private-user.u1'], 'notification.created', ['id' => 'n1']);

    Http::assertSent(function ($request): bool {
        $url = $request->url();

        if (! str_starts_with($url, 'https://api-ap1.pusher.com/apps/123/events')) {
            return false;
        }

        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

        $expected = hash_hmac(
            'sha256',
            "POST\n/apps/123/events\n".http_build_query([
                'auth_key' => 'test-key',
                'auth_timestamp' => $query['auth_timestamp'],
                'auth_version' => '1.0',
                'body_md5' => $query['body_md5'],
            ], '', '&', PHP_QUERY_RFC3986),
            'test-secret'
        );

        return $query['auth_key'] === 'test-key'
            && $query['auth_signature'] === $expected
            && $query['body_md5'] === md5((string) json_encode($request->data()))
            && $request['name'] === 'notification.created'
            && $request['channels'] === ['private-user.u1'];
    });
});
