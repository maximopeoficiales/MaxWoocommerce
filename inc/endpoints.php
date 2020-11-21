<?php

use Automattic\WooCommerce\Client;

function getDataConfig2()
{
     $args = array(
          'post_type' => 'max_functions_config',
          'posts_per_page' => 1,
          'orderby' => 'ID',
          'order' => 'ASC'
     );

     $datos = new WP_Query($args);
     $data = array();
     while ($datos->have_posts()) : $datos->the_post();
          $data["api_key_google_maps"] = get_field("api_key_google_maps");
          $data["consumer_key"] = get_field("consumer_key");
          $data["consumer_secret"] = get_field("consumer_secret");
          $data["latitud_tienda"] = get_field("latitud_tienda");
          $data["longitud_tienda"] = get_field("longitud_tienda");
     endwhile;
     return $data;
}
function max_functions_getWoocommerce2()
{
     $credenciales = getDataConfig2();
     $woocommerce = new Client(
          get_site_url(),
          $credenciales["consumer_key"],
          $credenciales["consumer_secret"],
          [
               'version' => 'wc/v3',
          ]
     );
     return $woocommerce;
}

function mfClients($params)
{
     $id = $params["id"];
     try {
          return max_functions_getWoocommerce2()->get("customers/$id");
     } catch (\Throwable $th) {
          return ["msg" => "No existe el cliente : $id"];
     }
}
add_action("rest_api_init", function () {
     register_rest_route("max_functions/v1", "/clients/(?P<id>\d+)", array(
          "methods" => "GET",
          "callback" => "mfClients",
          'args'            => array(),
     ));
});

/* funciones address */
function mfCreateAddress($user_id, $parameters)
{
     if (!mfExistsIdDest($user_id, $parameters["id_dest"])) {
          return add_user_meta($user_id, 'fabfw_address', $parameters);
     } else {
          return false;
     };
}
function mfExistsIdDest($user_id, $id_dest, $keyt = false)
{
     $direcciones = get_user_meta($user_id, 'fabfw_address', false);
     foreach ($direcciones as $key => $value) {
          if (intval($value["id_dest"]) == $id_dest) {
               if (!$keyt) {
                    return !empty($value) ? true : false;
               } else {
                    return $key;
               }
          }
     }
}

function mfUpdateAddress($user_id, $parameters)
{
     if (mfExistsIdDest($user_id, $parameters["id_dest"])) {
          $direcciones = get_user_meta($user_id, 'fabfw_address', false);
          $key = mfExistsIdDest($user_id, $parameters["id_dest"], true);
          update_user_meta($user_id, 'fabfw_address', $parameters, $direcciones[$key]);
          return true;
     } else {
          return false;
     }
}
function mfSecurityBasic($security)
{
     return ($security["user"] == "admin" && $security["pass"] == "admin999") ? true : false;
}
/* fin de funciones address */
/* endpoints  */
function mfPUTAddress($params)
{
     $params = $params->get_params();
     if (mfSecurityBasic($params["security"])) {
          $user_id = intval($params["user_id"]);
          return mfUpdateAddress($user_id, $params["data"]) ? ["status" => "200"] : ["status" => "400"];
     } else {
          return ["msg" => "Credenciales Erroneas"];
     }
}
function mfPOSTAddress($params)
{
     $params = $params->get_params();
     if (mfSecurityBasic($params["security"])) {
          $user_id = intval($params["user_id"]);
          return mfCreateAddress($user_id, $params["data"]) ? ["status" => "200"] : ["status" => "400"];
     } else {
          return ["msg" => "Credenciales Erroneas"];
     }
}
add_action("rest_api_init", function () {
     register_rest_route("max_functions/v1", "/address", array(
          "methods" => "POST",
          "callback" => "mfPOSTAddress",
          'args'            => array(),
     ));
});
add_action("rest_api_init", function () {
     register_rest_route("max_functions/v1", "/address", array(
          "methods" => "PUT",
          "callback" => "mfPUTAddress",
          'args'            => array(),
     ));
});
