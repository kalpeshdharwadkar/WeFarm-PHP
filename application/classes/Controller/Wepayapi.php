<?php defined('SYSPATH') or die('No direct script access.');

class Controller_Wepayapi extends Controller_Base {

    /**
     * Your Client ID
     */
    const CLIENT_ID = "153336";

    /**
     * Your Client Secret
     */
    const CLIENT_SECRET = "e913db7934";

    public function action_index() {

        if (Auth::instance()->logged_in()) {
            WePay::useStaging(self::CLIENT_ID, self::CLIENT_SECRET);
            $base_url = URL::site(NULL, TRUE);
            $redirect_uri = $base_url . 'wepayapi';
            $scope = WePay::$all_scopes;

            $user = Auth::instance()->get_user();
            $farmer = ORM::factory('farmer')->where('email', '=', $user->email)->find();

            if (empty($_GET['code'])) {
                $uri = WePay::getAuthorizationUri($scope, $redirect_uri);
                HTTP::redirect($uri);
            } 
            else {
                $info = WePay::getToken($_GET['code'], $redirect_uri);
                if ($info) {
                    $farmer->saveAccessToken($info->access_token);
                    $farmer->createAccount();
                    $this->template->content = "WePay Account Created! You can now purchase goods! <a href=\"" . URL::base() . "\">Back</a>";
                } 
                else {
                    // Unable to obtain access token
                    echo 'Unable to obtain access token from WePay.';
                }
            }
        } 
        else {
            $this->template->content = "Not Logged In";
        }
    }

    public static function create_checkout($merchant) {

        WePay::useStaging(self::CLIENT_ID, self::CLIENT_SECRET);
        $wepay = new WePay($merchant->getAccessToken());
        $response = $wepay->request('checkout/create/', array(
                    'account_id'          => $merchant->getAccountId(),
                    'short_description'   => "Purchasing ".$merchant->produce." from ".$merchant->name.".",
                    'type' 				  => 'goods',
                    'amount'			  => $merchant->produce_price,
                    'mode'				  => 'iframe'
                    ));
        return $response->checkout_uri;
    }
}