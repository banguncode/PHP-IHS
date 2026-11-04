<?php

namespace BangunCode\Ihs\Fhir;

/**
 * One OperationOutcome.issue entry.
 */
final class Issue
{
    /**
     * @var array<string, mixed>
     */
    private $data;

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * @return string|null fatal, error, warning or information.
     */
    public function getSeverity()
    {
        return isset($this->data['severity']) ? (string) $this->data['severity'] : null;
    }

    /**
     * @return string|null FHIR issue type, for example structure, value, duplicate, forbidden or suppressed.
     */
    public function getCode()
    {
        return isset($this->data['code']) ? (string) $this->data['code'] : null;
    }

    /**
     * @return string|null Human readable details.text, for example "reference_not_found".
     */
    public function getDetails()
    {
        return isset($this->data['details']['text']) ? (string) $this->data['details']['text'] : null;
    }

    /**
     * @return string|null
     */
    public function getDiagnostics()
    {
        return isset($this->data['diagnostics']) ? (string) $this->data['diagnostics'] : null;
    }

    /**
     * @return string[] FHIRPath expressions pointing at the offending element.
     */
    public function getExpression()
    {
        return isset($this->data['expression']) ? array_values((array) $this->data['expression']) : array();
    }

    /**
     * @return bool
     */
    public function isError()
    {
        return in_array($this->getSeverity(), array('fatal', 'error'), true);
    }

    /**
     * @return string Details, diagnostics and expression combined into one line.
     */
    public function getMessage()
    {
        $parts = array();
        if ($this->getDetails() !== null) {
            $parts[] = $this->getDetails();
        }
        if ($this->getDiagnostics() !== null && $this->getDiagnostics() !== $this->getDetails()) {
            $parts[] = $this->getDiagnostics();
        }
        $message = $parts ? implode(': ', $parts) : (string) $this->getCode();
        if ($this->getExpression()) {
            $message .= ' [' . implode(', ', $this->getExpression()) . ']';
        }

        return $message;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray()
    {
        return $this->data;
    }
}
