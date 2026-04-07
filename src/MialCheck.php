<?php

declare(strict_types=1);

namespace Coffeemakr\Mial;

use Algo26\IdnaConvert\Exception\AlreadyPunycodeException;
use Algo26\IdnaConvert\Exception\InvalidCharacterException;
use Algo26\IdnaConvert\ToIdn;
use Algo26\IdnaConvert\ToUnicode;
use Coffeemakr\Mial\Exception\InvalidAddressFormatException;
use Coffeemakr\Mial\Exception\InvalidLocalPartException;
use Coffeemakr\Mial\Exception\InvalidTopLevelDomainException;

class MialCheck
{

    private readonly string $local_part;
    private readonly string $host;
    private readonly string $original_mail;
    private ?\Exception $addressException;


    public function __construct(#[\SensitiveParameter] string $untrusted_email)
    {
        $this->original_mail = $untrusted_email;

        $parts = [];
        if ($untrusted_email) {
            $parts = explode('@', $untrusted_email, 3);
        }
        if (count($parts) == 2) {
            $local_part = $parts[0];
            $host = $parts[1];

            $idn = new ToIdn();
            try {
                try {
                    $host = $idn->convert($host);
                } catch (AlreadyPunycodeException $e) {
                    // We are not sure, the punnycode is correct...
                    $unicode = new ToUnicode();
                    $host = $unicode->convert($host);

                    $host = $idn->convert($host);
                }
            } catch (InvalidCharacterException $e) {
                $this->addressException = $e;
                return;
            }
        } else {
            $this->addressException = new InvalidAddressFormatException("Invalid number of '@' characters");
            return;
        }

        $filtered_mail = filter_var($local_part . '@' . $host, FILTER_VALIDATE_EMAIL);
        if (!$filtered_mail) {
            $this->addressException = new InvalidLocalPartException("Invalid local part");
        }

        $host_parts = explode('.', $host);
        if (count($host_parts) < 2) {
            // No TLD
            $this->addressException = new InvalidTopLevelDomainException('');
            return;
        }
        $tld = end($host_parts);
        if (!TopLevelDomains::isValid($tld)) {
            // Invalid TLD
            $this->addressException = new InvalidTopLevelDomainException($tld);
            return;
        }

        $this->host = $host;
        $this->local_part = $local_part;
        $this->addressException = NULL;
    }

    public function isValid(): bool
    {
        return $this->addressException === NULL;
    }

    public function isNormalized(): bool
    {
        if (!$this->isValid()) return false;
        return $this->original_mail === $this->getAddress();
    }

    public function getError(): \Exception|null {
        return $this->addressException;
    }

    public function getAddress(): string
    {
        if (!$this->isValid()) return '';
        return $this->local_part . '@' . $this->host;
    }

    public function __toString()
    {
        return $this->getAddress();
    }
}
