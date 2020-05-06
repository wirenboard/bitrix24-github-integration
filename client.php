<?php

use Bitrix24\Bitrix24;
use Bitrix24\Exceptions\Bitrix24ApiException;
use Bitrix24\Exceptions\Bitrix24EmptyResponseException;
use Bitrix24\Exceptions\Bitrix24Exception;
use Bitrix24\Exceptions\Bitrix24IoException;
use Bitrix24\Exceptions\Bitrix24MethodNotFoundException;
use Bitrix24\Exceptions\Bitrix24PaymentRequiredException;
use Bitrix24\Exceptions\Bitrix24PortalDeletedException;
use Bitrix24\Exceptions\Bitrix24PortalRenamedException;
use Bitrix24\Exceptions\Bitrix24TokenIsExpiredException;
use Bitrix24\Exceptions\Bitrix24TokenIsInvalidException;
use Bitrix24\Exceptions\Bitrix24WrongClientException;

class Client
{
    /**
     * @var Bitrix24
     */
    private $_bitrix24;

    /**
     * @var string
     */
    private static $config = __DIR__.'/auth';

    /**
     * Client constructor.
     * @throws Bitrix24ApiException
     * @throws Bitrix24EmptyResponseException
     * @throws Bitrix24Exception
     * @throws Bitrix24IoException
     * @throws Bitrix24MethodNotFoundException
     * @throws Bitrix24PaymentRequiredException
     * @throws Bitrix24PortalDeletedException
     * @throws Bitrix24PortalRenamedException
     * @throws Bitrix24TokenIsExpiredException
     * @throws Bitrix24TokenIsInvalidException
     * @throws Bitrix24WrongClientException
     */
    public function __construct() {
        $params = self::load();

        $this->_bitrix24 = new Bitrix24(false);

        $this->_bitrix24->setApplicationScope($params['B24_APPLICATION_SCOPE']);
        $this->_bitrix24->setApplicationId($params['B24_APPLICATION_ID']);
        $this->_bitrix24->setApplicationSecret($params['B24_APPLICATION_SECRET']);
        $this->_bitrix24->setRedirectUri($params['B24_REDIRECT_URI']);
        $this->_bitrix24->setDomain($params['DOMAIN']);
        $this->_bitrix24->setMemberId($params['MEMBER_ID']);
        $this->_bitrix24->setAccessToken($params['AUTH_ID']);
        $this->_bitrix24->setRefreshToken($params['REFRESH_ID']);

        if ($this->_bitrix24->isAccessTokenExpire()) {
            $temp = $this->_bitrix24->getNewAccessToken();

            $params['AUTH_ID'] = $temp['access_token'];
            $params['REFRESH_ID'] = $temp['refresh_token'];

            $this->_bitrix24->setAccessToken($params['AUTH_ID']);
            $this->_bitrix24->setRefreshToken($params['REFRESH_ID']);

            self::save($params);
        }
    }

    /**
     * Get instance
     * @return Bitrix24
     */
    public function getBitrix24() {
        return $this->_bitrix24;
    }

    /**
     * Save config
     *
     * @param array $params
     *
     * @return bool
     */
    public static function save(array $params) {
        $result = json_encode($params, JSON_UNESCAPED_UNICODE);
        return file_put_contents(self::$config, $result) > 0;
    }

    /**
     * Load config
     *
     * @return array
     */
    public static function load() {
        if (!file_exists(self::$config)) {
            return [];
        }

        $params = file_get_contents(self::$config);

        return json_decode($params, true);
    }

    /**
     * Check install
     * @return bool
     */
    public static function check() {
        try {
            $params = self::load();
            $client = new self();
            $result = $client->getBitrix24()->call('app.info');
            return $result['result']['CODE'] === $params['B24_APPLICATION_ID'];
        } catch (\Exception $e) {
            return false;
        }
    }
}