<?php

namespace BangunCode\Ihs\Fhir;

/**
 * Read access to a FHIR OperationOutcome resource.
 */
final class OperationOutcome
{
    /**
     * @var array<string, mixed>
     */
    private $data;

    /**
     * @var Issue[]
     */
    private $issues = array();

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(array $data)
    {
        $this->data = $data;
        foreach (isset($data['issue']) ? (array) $data['issue'] : array() as $issue) {
            if (is_array($issue)) {
                $this->issues[] = new Issue($issue);
            }
        }
    }

    /**
     * @param mixed $resource
     *
     * @return bool
     */
    public static function isOperationOutcome($resource)
    {
        return is_array($resource) && isset($resource['resourceType']) && $resource['resourceType'] === 'OperationOutcome';
    }

    /**
     * @return Issue[]
     */
    public function getIssues()
    {
        return $this->issues;
    }

    /**
     * @return Issue[] Issues with severity fatal or error.
     */
    public function getErrors()
    {
        return array_values(array_filter($this->issues, function (Issue $issue) {
            return $issue->isError();
        }));
    }

    /**
     * @return bool
     */
    public function hasErrors()
    {
        return count($this->getErrors()) > 0;
    }

    /**
     * @param string[] $codes
     *
     * @return bool
     */
    public function hasIssueCode(array $codes)
    {
        foreach ($this->issues as $issue) {
            if (in_array($issue->getCode(), $codes, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Whether the server reported that the resource already exists, for example
     * "Found duplicate: Patient" or "Patient Resource Exists".
     *
     * @return bool
     */
    public function isDuplicate()
    {
        foreach ($this->issues as $issue) {
            if ($issue->getCode() === 'duplicate' || stripos((string) $issue->getDetails(), 'exists') !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return string All issue messages joined with "; ".
     */
    public function getMessage()
    {
        return implode('; ', array_map(function (Issue $issue) {
            return $issue->getMessage();
        }, $this->issues));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray()
    {
        return $this->data;
    }
}
