<?php

namespace Drupal\stanford_person_importer;

use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Provider\GenericProvider;
use Psr\Http\Message\ResponseInterface;

/**
 * OAuth2 provider for the CAP API authorization service.
 *
 * The generic provider only fails when the response body contains an "error"
 * key. This also fails on any unsuccessful HTTP status code.
 */
class CapOauthProvider extends GenericProvider {

  /**
   * {@inheritDoc}
   */
  protected function checkResponse(ResponseInterface $response, $data) {
    parent::checkResponse($response, $data);

    if ($response->getStatusCode() >= 400) {
      throw new IdentityProviderException($response->getReasonPhrase() ?: 'Unable to acquire access token', $response->getStatusCode(), $data);
    }
  }

}
