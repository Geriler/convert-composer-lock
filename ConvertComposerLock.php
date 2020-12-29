<?php

class ConvertComposerLock
{
    private array $depends = [];
    private array $dependsDev = [];
    private array $require = [];
    private array $requireDev = [];

    public function __construct(string $path)
    {
        $json = file_get_contents($path);
        $this->setDepends($json);
        $this->setDependsDev($json);
        $this->setRequire($this->getDepends());
        $this->setRequireDev($this->getDependsDev());
        $this->cleanRequire();
    }

    /**
     * @return array
     */
    private function getDepends(): array
    {
        return $this->depends;
    }

    /**
     * @param string $json
     */
    private function setDepends(string $json): void
    {
        $composer = json_decode($json);
        $depends = [];
        $requireDev = 'require-dev';
        foreach ($composer->packages as $key => $depend) {
            $obj = new stdClass();
            $obj->version = $depend->version;
            $obj->require = $depend->require;
            $obj->require_dev = $depend->$requireDev;
            $depends[$depend->name] = $obj;
        }
        $this->depends = $depends;
    }

    /**
     * @return array
     */
    private function getDependsDev(): array
    {
        return $this->dependsDev;
    }

    /**
     * @param string $json
     */
    private function setDependsDev(string $json): void
    {
        $composer = json_decode($json);
        $dependsDev = [];
        $requireDev = 'require-dev';
        $packagesDev = 'packages-dev';
        foreach ($composer->$packagesDev as $key => $depend) {
            $obj = new stdClass();
            $obj->version = $depend->version;
            $obj->require = $depend->require;
            $obj->require_dev = $depend->$requireDev;
            $dependsDev[$depend->name] = $obj;
        }
        $this->dependsDev = $dependsDev;
    }

    /**
     * @return array
     */
    public function getRequire(): array
    {
        return $this->require;
    }

    /**
     * @param array $depends
     */
    private function setRequire(array $depends): void
    {
        $require = [];
        foreach ($depends as $name => $info) {
            if (isset($info->require)) {
                $require[$name] = '^' . $info->version;
            } else {
                if (!is_null($parent = $this->getParentRequire($name, $depends))) {
                    if (!array_key_exists($parent->name, $require)) {
                        $require[$parent->name] = '^' . $parent->version;
                    }
                } else {
                    $require[$name] = '^' . $info->version;
                }
            }
        }
        ksort($require);
        $this->require = $require;
    }

    /**
     * @return array
     */
    public function getRequireDev(): array
    {
        return $this->requireDev;
    }

    /**
     * @param array $depends
     */
    private function setRequireDev(array $depends): void
    {
        $requireDev = [];
        foreach ($depends as $name => $info) {
            if (isset($info->require_dev)) {
                foreach ($info->require_dev as $nameRequireDev => $versionRequireDev) {
                    if (isset($depends[$nameRequireDev])) {
                        if (array_key_exists($nameRequireDev, $requireDev)) {
                            $version = $this->getMinVersion($requireDev[$nameRequireDev], $versionRequireDev);
                            $requireDev[$nameRequireDev] = $version;
                        } else {
                            $requireDev[$nameRequireDev] = $versionRequireDev;
                        }
                    }
                }
            } else {
                if (!is_null($parent = $this->getParentRequire($name, $depends))) {
                    if (!array_key_exists($parent->name, $requireDev)) {
                        $requireDev[$parent->name] = $parent->version;
                    }
                } else {
                    $requireDev[$name] = $info->version;
                }
            }
        }
        ksort($requireDev);
        $this->requireDev = $requireDev;
    }

    /**
     * @param string $require
     * @param array $depends
     * @return array|null
     */
    private function getParentRequire(string $require, array $depends): ?stdClass
    {
        foreach ($depends as $name => $info) {
            if (isset($info->require)) {
                foreach ($info->require as $nameRequire => $versionRequire) {
                    if ($require == $nameRequire) {
                        $obj = new stdClass();
                        $obj->name = $name;
                        $obj->version = $info->version;
                        return $obj;
                    }
                }
            }
        }
        return null;
    }

    /**
     * @param string $version1
     * @param string $version2
     * @return string
     */
    private function getMinVersion(string $version1, string $version2): string
    {
        if (version_compare($version1, $version2, '<')) {
            return $version1;
        } else {
            return $version2;
        }
    }

    /**
     *
     */
    private function cleanRequire(): void
    {
        $newRequire = $this->getRequire();
        foreach ($this->getRequireDev() as $name => $version) {
            if (array_key_exists($name, $newRequire)) {
                unset($newRequire[$name]);
            }
        }
        $this->require = $newRequire;
    }
}
