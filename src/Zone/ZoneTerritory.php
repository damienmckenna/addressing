<?php

namespace CommerceGuys\Addressing\Zone;

use CommerceGuys\Addressing\AddressInterface;
use CommerceGuys\Addressing\PostalCodeHelper;

/**
 * Represents a zone territory.
 */
class ZoneTerritory
{
    protected ?string $countryCode;

    protected ?string $administrativeArea;

    protected ?string $locality;

    protected ?string $dependentLocality;

    /**
     * The included postal codes.
     *
     * Can be a regular expression ("/(35|38)[0-9]{3}/") or a comma-separated
     * list of postal codes, including ranges ("98, 100:200, 250").
     */
    protected ?string $includedPostalCodes;

    /**
     * The excluded postal codes.
     *
     * Can be a regular expression ("/(35|38)[0-9]{3}/") or a comma-separated
     * list of postal codes, including ranges ("98, 100:200, 250").
     */
    protected ?string $excludedPostalCodes;


    public function __construct(array $definition)
    {
        if (empty($definition['country_code'])) {
            throw new \InvalidArgumentException('Missing required property "country_code".');
        }

        $this->countryCode = $definition['country_code'];
        $this->administrativeArea = !empty($definition['administrative_area']) ? $definition['administrative_area'] : null;
        $this->locality = !empty($definition['locality']) ? $definition['locality'] : null;
        $this->dependentLocality = !empty($definition['dependent_locality']) ? $definition['dependent_locality'] : null;
        $this->includedPostalCodes = !empty($definition['included_postal_codes']) ? $definition['included_postal_codes'] : null;
        $this->excludedPostalCodes = !empty($definition['excluded_postal_codes']) ? $definition['excluded_postal_codes'] : null;
    }

    public function getCountryCode(): string
    {
        return $this->countryCode;
    }

    /**
     * Gets the administrative area.
     *
     * @return string|null The administrative area, or null if all should match.
     */
    public function getAdministrativeArea(): ?string
    {
        return $this->administrativeArea;
    }

    /**
     * Gets the locality.
     *
     * @return string|null The locality, or null if all should match.
     */
    public function getLocality(): ?string
    {
        return $this->locality;
    }

    /**
     * Gets the dependent locality.
     *
     * @return string|null The dependent locality, or null if all should match.
     */
    public function getDependentLocality(): ?string
    {
        return $this->dependentLocality;
    }

    /**
     * Gets the included postal codes.
     *
     * @return string|null The included postal codes, or null if all should match.
     */
    public function getIncludedPostalCodes(): ?string
    {
        return $this->includedPostalCodes;
    }

    /**
     * Gets the excluded postal codes.
     *
     * @return string|null The excluded postal codes, or null if all should match.
     */
    public function getExcludedPostalCodes(): ?string
    {
        return $this->excludedPostalCodes;
    }

    /**
     * Checks whether the provided address belongs to the territory.
     */
    public function match(AddressInterface $address): bool
    {
        if ($address->getCountryCode() !== $this->countryCode) {
            return false;
        }
        if ($this->administrativeArea && $this->administrativeArea !== $address->getAdministrativeArea()) {
            return false;
        }
        if ($this->locality && $this->locality !== $address->getLocality()) {
            return false;
        }
        if ($this->dependentLocality && $this->dependentLocality !== $address->getDependentLocality()) {
            return false;
        }
        if (!PostalCodeHelper::match($address->getPostalCode(), $this->includedPostalCodes, $this->excludedPostalCodes)) {
            return false;
        }

        return true;
    }
}
