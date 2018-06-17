<?php

namespace Drupal\redis_session\Session\Storage;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Utility\Error;
use Drupal\redis\ClientFactory;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Storage\Proxy\AbstractProxy;

/**
 * @author Jens Schulze, github.com/jensschulze
 */
class RedisSessionHandler extends AbstractProxy implements \SessionHandlerInterface
{

    use DependencySerializationTrait;

    /**
     * @var \Symfony\Component\HttpFoundation\RequestStack
     */
    protected $requestStack;

    /**
     * @var \Redis
     */
    protected $redis;

    private $prefix = 'session';

    private $ttl = 2000000;

    public function __construct(RequestStack $request_stack, ClientFactory $clientFactory)
    {
        $this->requestStack = $request_stack;
        $this->redis = $clientFactory::getClient();
    }

    /**
     * {@inheritdoc}
     */
    public function open($save_path, $name): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function read($sid): string
    {
        if (empty($sid)) {
            return '';
        }

        // Read the session data from the database.
        $key = $this->getKey($sid);
        if (!$this->redis->exists($key)) {
            return '';
        }

        return $this->redis->get($key);
    }

    /**
     * {@inheritdoc}
     */
    public function write($sid, $value): bool
    {
        // The exception handler is not active at this point, so we need to do it
        // manually.
        try {
            $request = $this->requestStack->getCurrentRequest();
//            $fields = [
//                'uid' => $request->getSession()->get('uid', 0),
//                'hostname' => $request->getClientIP(),
//                'session' => $value,
//                'timestamp' => REQUEST_TIME,
//            ];
            $key = $this->getKey($sid);

            $this->redis->setex($key, $this->ttl, $value);

            return true;
        } catch (\Exception $exception) {
            require_once DRUPAL_ROOT.'/core/includes/errors.inc';
            // If we are displaying errors, then do so with no possibility of a
            // further uncaught exception being thrown.
            if (error_displayable()) {
                print '<h1>Uncaught exception thrown in session handler.</h1>';
                print '<p>'.Error::renderExceptionSafe($exception).'</p><hr />';
            }

            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function close(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function destroy($sid): bool
    {
        // Delete session data.
        $this->redis->delete($this->getKey($sid));

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function gc($lifetime): bool
    {
        return true;
    }

    private function getKey(string $sid): string
    {
        return sprintf('%s_%s', $this->prefix, Crypt::hashBase64($sid));
    }
}
