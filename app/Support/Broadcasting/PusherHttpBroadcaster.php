<?php

declare(strict_types=1);

namespace App\Support\Broadcasting;

use Illuminate\Broadcasting\Broadcasters\Broadcaster;
use Illuminate\Broadcasting\Broadcasters\UsePusherChannelConventions;
use Illuminate\Broadcasting\BroadcastException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Driver broadcast Pusher tanpa SDK (CHT-2, NTF-2).
 *
 * Dipakai karena `pusher/pusher-php-server` v7 membutuhkan guzzle 7,
 * sementara Laravel 13 mengunci guzzle 8. Protokol yang dipakai hanya
 * dua endpoint publik Pusher: otorisasi kanal privat (HMAC lokal, tanpa
 * jaringan) dan trigger event (satu POST HTTPS).
 *
 * Didaftarkan dengan nama `pusher` agar konfigurasi `.env`
 * (`BROADCAST_CONNECTION=pusher`) tidak berubah.
 */
final class PusherHttpBroadcaster extends Broadcaster
{
    use UsePusherChannelConventions;

    public function __construct(
        private readonly string $appId,
        private readonly string $key,
        private readonly string $secret,
        private readonly string $cluster,
        private readonly ?string $host = null,
        private readonly int $port = 443,
        private readonly string $scheme = 'https',
    ) {}

    /**
     * @param  Request  $request
     * @return mixed
     *
     * @throws AccessDeniedHttpException
     */
    public function auth($request)
    {
        $channelName = $this->normalizeChannelName($request->channel_name);

        if (empty($request->channel_name) ||
            ($this->isGuardedChannel($request->channel_name) &&
            ! $this->retrieveUser($request, $channelName))) {
            throw new AccessDeniedHttpException;
        }

        return parent::verifyUserCanAccessChannel(
            $request, $channelName
        );
    }

    /**
     * @param  Request  $request
     * @param  mixed  $result
     * @return mixed
     */
    public function validAuthenticationResponse($request, $result)
    {
        return [
            'auth' => $this->key.':'.$this->socketAuth((string) $request->socket_id, (string) $request->channel_name),
        ];
    }

    /**
     * @param  array<int, string>  $channels
     * @param  string  $event
     * @param  array<string, mixed>  $payload
     *
     * @throws BroadcastException
     */
    public function broadcast(array $channels, $event, array $payload = [])
    {
        unset($payload['socket']);

        $body = json_encode([
            'name' => $event,
            'channels' => $this->formatChannels($channels),
            'data' => json_encode($payload),
        ]);

        if ($body === false) {
            throw new BroadcastException('Gagal mengodekan payload broadcast Pusher.');
        }

        $path = "/apps/{$this->appId}/events";
        $query = [
            'auth_key' => $this->key,
            'auth_timestamp' => (string) time(),
            'auth_version' => '1.0',
            'body_md5' => md5($body),
        ];
        ksort($query);

        $query['auth_signature'] = hash_hmac(
            'sha256',
            "POST\n{$path}\n".http_build_query($query, '', '&', PHP_QUERY_RFC3986),
            $this->secret
        );

        $url = "{$this->scheme}://{$this->endpointHost()}{$path}?".http_build_query($query, '', '&', PHP_QUERY_RFC3986);

        try {
            Http::timeout(5)->withBody($body, 'application/json')->post($url)->throw();
        } catch (\Throwable $exception) {
            throw new BroadcastException('Pusher error: '.$exception->getMessage());
        }
    }

    private function socketAuth(string $socketId, string $channel): string
    {
        return hash_hmac('sha256', $socketId.':'.$channel, $this->secret);
    }

    private function endpointHost(): string
    {
        $host = $this->host !== null && $this->host !== ''
            ? $this->host
            : "api-{$this->cluster}.pusher.com";

        $defaultPort = $this->scheme === 'https' ? 443 : 80;

        return $this->port === $defaultPort ? $host : "{$host}:{$this->port}";
    }
}
