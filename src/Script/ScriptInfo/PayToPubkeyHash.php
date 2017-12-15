<?php

namespace BitWasp\Bitcoin\Script\ScriptInfo;

use BitWasp\Bitcoin\Crypto\EcAdapter\Key\PublicKeyInterface;
use BitWasp\Bitcoin\Script\Opcodes;
use BitWasp\Bitcoin\Script\Parser\Operation;
use BitWasp\Bitcoin\Script\ScriptInterface;
use BitWasp\Bitcoin\Script\ScriptType;
use BitWasp\Buffertools\BufferInterface;

class PayToPubkeyHash
{

    /**
     * @var BufferInterface
     */
    private $hash;

    /**
     * @var bool
     */
    private $verify;

    /**
     * PayToPubkeyHash constructor.
     * @param $opcode
     * @param BufferInterface $hash160
     * @param bool $allowVerify
     */
    public function __construct($opcode, BufferInterface $hash160, $allowVerify = false)
    {
        if ($hash160->getSize() !== 20) {
            throw new \RuntimeException('Malformed pay-to-pubkey-hash script');
        }

        if ($opcode === Opcodes::OP_CHECKSIG) {
            $verify = false;
        } else if ($allowVerify && $opcode === Opcodes::OP_CHECKSIGVERIFY) {
            $verify = true;
        } else {
            throw new \RuntimeException("Malformed pay-to-pubkey-hash script - invalid opcode");
        }

        $this->hash = $hash160;
        $this->opcode = $opcode;
        $this->verify = $verify;
    }

    /**
     * @param Operation[] $chunks
     * @param bool $allowVerify
     * @return static
     */
    public static function fromDecodedScript(array $chunks, $allowVerify = false)
    {
        if (count($chunks) !== 5) {
            throw new \RuntimeException('Malformed pay-to-pubkey-hash script');
        }

        if ($chunks[0]->getOp() !== Opcodes::OP_DUP
            || $chunks[1]->getOp() !== Opcodes::OP_HASH160
            || $chunks[3]->getOp() !== Opcodes::OP_EQUALVERIFY
        ) {
            throw new \RuntimeException('Malformed pay-to-pubkey-hash script');
        }

        return new static($chunks[4]->getOp(), $chunks[2]->getData(), $allowVerify);
    }

    /**
     * @param ScriptInterface $script
     * @param bool $allowVerify
     * @return PayToPubkeyHash
     */
    public static function fromScript(ScriptInterface $script, $allowVerify = false)
    {
        return self::fromDecodedScript($script->getScriptParser()->decode(), $allowVerify);
    }

    public function getType()
    {
        return ScriptType::P2PK;
    }

    /**
     * @return int
     */
    public function getRequiredSigCount()
    {
        return 1;
    }

    /**
     * @return int
     */
    public function getKeyCount()
    {
        return 1;
    }

    /**
     * @return bool
     */
    public function isChecksigVerify()
    {
        return $this->verify;
    }

    /**
     * @param PublicKeyInterface $publicKey
     * @return bool
     */
    public function checkInvolvesKey(PublicKeyInterface $publicKey)
    {
        return $publicKey->getPubKeyHash()->equals($this->hash);
    }

    /**
     * @return BufferInterface
     */
    public function getPubKeyHash()
    {
        return $this->hash;
    }
}
