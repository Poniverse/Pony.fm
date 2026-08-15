<?php

/**
 * Copyright 2016 Peter Deltchev
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Poniverse\Lib;

/**
 * Class AccessToken
 *
 * A container for the fields in the draft OAuth Token Introspection proposal.
 *
 * @link https://tools.ietf.org/html/draft-richer-oauth-introspection-06
 * @package Poniverse
 */
class AccessToken {
    protected $token;

    protected $isActive;
    protected $expiresAt;
    protected $issuedAt;
    protected $scopes;
    protected $clientId;
    protected $sub;
    protected $userId;
    protected $intendedAudience;
    protected $issuer;
    protected $tokenType;

    public function __construct($accessToken) {
        $this->token = $accessToken;
    }

    /**
     * @return mixed
     */
    public function getToken() {
        return $this->token;
    }

    /**
     * @return bool
     */
    public function getIsActive() {
        return $this->isActive;
    }

    /**
     * @param bool $isActive
     * @return AccessToken
     */
    public function setIsActive($isActive) {
        $this->isActive = $isActive;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getExpiresAt() {
        return $this->expiresAt;
    }

    /**
     * @param mixed $expiresAt
     * @return AccessToken
     */
    public function setExpiresAt($expiresAt) {
        $this->expiresAt = $expiresAt;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getIssuedAt() {
        return $this->issuedAt;
    }

    /**
     * @param mixed $issuedAt
     * @return AccessToken
     */
    public function setIssuedAt($issuedAt) {
        $this->issuedAt = $issuedAt;
        return $this;
    }

    /**
     * @return array
     */
    public function getScopes() {
        return $this->scopes;
    }

    /**
     * @param array|string $scopes
     * @return AccessToken
     */
    public function setScopes($scopes) {
        if (is_array($scopes)) {
            $this->scopes = $scopes;
        } else {
            $this->scopes = mb_split(' ', $scopes);
        }

        return $this;
    }

    /**
     * @return mixed
     */
    public function getClientId() {
        return $this->clientId;
    }

    /**
     * @param mixed $clientId
     * @return AccessToken
     */
    public function setClientId($clientId) {
        $this->clientId = $clientId;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getSub() {
        return $this->sub;
    }

    /**
     * @param mixed $sub
     * @return AccessToken
     */
    public function setSub($sub) {
        $this->sub = $sub;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getUserId() {
        return $this->userId;
    }

    /**
     * @param mixed $userId
     * @return AccessToken
     */
    public function setUserId($userId) {
        $this->userId = $userId;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getIntendedAudience() {
        return $this->intendedAudience;
    }

    /**
     * @param mixed $intendedAudience
     * @return AccessToken
     */
    public function setIntendedAudience($intendedAudience) {
        $this->intendedAudience = $intendedAudience;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getIssuer() {
        return $this->issuer;
    }

    /**
     * @param mixed $issuer
     * @return AccessToken
     */
    public function setIssuer($issuer) {
        $this->issuer = $issuer;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getTokenType() {
        return $this->tokenType;
    }

    /**
     * @param mixed $tokenType
     * @return AccessToken
     */
    public function setTokenType($tokenType) {
        $this->tokenType = $tokenType;
        return $this;
    }
}
