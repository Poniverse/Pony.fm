<?php

/**
 * Pony.fm - A community for pony fan music.
 * Copyright (C) 2015 Peter Deltchev
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace Poniverse;

/**
 * Class AccessTokenInfo
 *
 * A container for the fields in the draft OAuth Token Introspection proposal.
 *
 * @link https://tools.ietf.org/html/draft-richer-oauth-introspection-06
 * @package Poniverse
 */
class AccessTokenInfo {
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
     * @return AccessTokenInfo
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
     * @return AccessTokenInfo
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
     * @return AccessTokenInfo
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
     * @return AccessTokenInfo
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
     * @return AccessTokenInfo
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
     * @return AccessTokenInfo
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
     * @return AccessTokenInfo
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
     * @return AccessTokenInfo
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
     * @return AccessTokenInfo
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
     * @return AccessTokenInfo
     */
    public function setTokenType($tokenType) {
        $this->tokenType = $tokenType;
        return $this;
    }
}
