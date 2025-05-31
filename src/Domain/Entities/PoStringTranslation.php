<?php

declare(strict_types=1);

namespace Domain\Entities;

use Domain\Entities\PoString;
use Domain\Entities\Language;

/**
 * @Entity
 * @Table(name="po_string_translations")
 **/
class PoStringTranslation
{
    /**
     * @Id
     * @GeneratedValue
     * @Column(type="integer")
     */
    private int $id;

    /**
     * @ManyToOne(targetEntity="PoString", inversedBy="translations")
     * @JoinColumn(name="po_string_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private PoString $poString;

    /**
     * @ManyToOne(targetEntity="Language")
     * @JoinColumn(name="language_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private Language $language;

    /**
     * @Column(type="string", name="translation")
     */
    private string $translation;

    public function __construct
    (
        PoString $poString,
        Language $language,
        string $translation
    ) {
        $this->setPoString($poString);
        $this->setLanguage($language);
        $this->setTranslation($translation);
    }

    public function getPoString(): PoString
    {
        return $this->poString;
    }

    public function getLocale(): Language
    {
        return $this->language;
    }

    public function getTranslation(): string
    {
        return $this->translation;
    }

    public function setPoString(PoString $poString): void
    {
    	$this->poString = $poString;
    }

    public function setLocale(Language $language): void
    {
        $this->language = $language;
    }

    public function setTranslation(string $translation): void
    {
        $this->translation = $translation;
    }
}