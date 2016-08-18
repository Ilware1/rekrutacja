<?php

namespace GeneratorBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

class GenerateFileCommand extends ContainerAwareCommand
{

    static $ITERATION = 100000;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('file:generate')
            ->setDescription('generates file with the specified parameters')
            ->addOption('maximum', 'm', InputOption::VALUE_OPTIONAL, 'maximum allowed number')
            ->addOption('minimum', 'x', InputOption::VALUE_OPTIONAL, 'minimum allowed number')
            ->addOption('decimal_places', 'd', InputOption::VALUE_OPTIONAL, 'number of decimal places')
            ->addOption('file_size', 's', InputOption::VALUE_OPTIONAL, 'file size to generate (when to stop generating, ie. 5KB, 5MB, 5GB)')
            ->addOption('name', 'o', InputOption::VALUE_OPTIONAL, 'name of the output file');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $aConfigParameter = [];
        $aConfigParameter['minimum'] = $this->validateIntegerValue($input, $output, 'minimum', new Question('<fg=green>Please enter the minimum allowed number</><fg=yellow>[0]:</>', 0));
        $aConfigParameter['maximum'] = $this->validateIntegerValue($input, $output, 'maximum', new Question('<fg=green>Please enter the maximum allowed number</><fg=yellow>[5000]:</>', 5000), $aConfigParameter['minimum']);
        $aConfigParameter['decimal_places'] = $this->validateIntegerValue($input, $output, 'decimal_places', new Question('<fg=green>Please enter the number of decimal places</><fg=yellow>[3]:</>', 3));
        $aConfigParameter['file_size'] = $this->validateFileSizeValue($input, $output);
        $aConfigParameter['name'] = $this->validateFileNameValue($input, $output);
        $NumberGenerator = $this->getContainer()->get('generator.number');
        $rFile = $this->getFileHandle($aConfigParameter['name']);
        $iSizeInBites = $this->convertToBytes($aConfigParameter['file_size']);
        $iIterationEstimat = $iSizeInBites / $this->getOneRowSize($NumberGenerator, $aConfigParameter);
        $progress = new ProgressBar($output, ceil($iIterationEstimat));
        $progress->start();
        $progress->setFormat('[%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s%');
        while (fstat($rFile)['size'] < $iSizeInBites) {
            $sRow = "";
            $iMissingFileSize = $iSizeInBites - fstat($rFile)['size'];
            for ($i = 0; $i < self::$ITERATION; $i++) {
                $sRow .= $NumberGenerator->getNextNumber($aConfigParameter['minimum'], $aConfigParameter['maximum'], $aConfigParameter['decimal_places']) . "|";
                if (strlen($sRow) >= $iMissingFileSize) {
                    break;
                }
            }
            $sRow = substr($sRow, 0, -1) . "\n";
            $progress->advance();
            fwrite($rFile, $sRow);
        }
        fclose($rFile);
        $progress->finish();
        $output->writeln('');
    }

    /**
     * Pobiera rozmiar jednego wiersza , używane do estymacji ilości ktoków
     * @param $NumberGenerator
     * @param $aConfigParameter
     * @return mixed
     */
    protected function getOneRowSize($NumberGenerator, $aConfigParameter)
    {
        $sRow = "";
        for ($i = 0; $i < self::$ITERATION; $i++) {
            $sRow .= $NumberGenerator->getNextNumber($aConfigParameter['minimum'], $aConfigParameter['maximum'], $aConfigParameter['decimal_places']) . "|";
        }
        return strlen($sRow);
    }

    /**
     * Otwiera plik do zapisu danych
     * @param $sFileName
     * @return mixed
     */
    protected function getFileHandle($sFileName)
    {
        return fopen($this->getContainer()->get('kernel')->getRootDir() . "/../var/" . $sFileName . ".txt", "w");
    }

    /**
     * Zamienia podany rozmar pliku na bajty
     * @param string $sFrom
     * @return int
     */
    protected function convertToBytes(string $sFrom):int
    {
        $iNumber = substr($sFrom, 0, -2);
        switch (strtoupper(substr($sFrom, -2))) {
            case "KB":
                return $iNumber * 1024;
            case "MB":
                return $iNumber * pow(1024, 2);
            case "GB":
                return $iNumber * pow(1024, 3);
            case "TB":
                return $iNumber * pow(1024, 4);
            case "PB":
                return $iNumber * pow(1024, 5);
            default:
                return $sFrom;
        }
    }

    /**
     * Sprawdza popraność wpisane nazwy pliku, i jeżeli jest błędna prosi o ponowne podane
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return string
     */
    protected function validateFileNameValue(InputInterface $input, OutputInterface $output)
    {
        $mOptionValue = $input->getOption('name');
        if (empty($mOptionValue)) {
            $Helper = $this->getHelper('question');
            $sName = md5(microtime(true));
            $question = new Question('<fg=green>Please enter the name of file</><fg=yellow>[' . $sName . ']:</>', $sName);
            $question->setValidator(function ($answer) {
                if (preg_match('/[^A-Za-z0-9 _ .-]/', $answer) || empty($answer)) {
                    throw new \RuntimeException(
                        'The name "' . $answer . '" is incorrect. The file name can only contain "a-Z", "0-9" and "_ . -", not null'
                    );
                }
                return $answer;
            });
            return $Helper->ask($input, $output, $question);
        }
        return $mOptionValue;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return mixed
     */
    protected function validateFileSizeValue(InputInterface $input, OutputInterface $output)
    {
        $mOptionValue = $input->getOption('file_size');
        if (empty($mOptionValue) || !preg_match('/^\d+(kb|mb|gb)$/i', $mOptionValue)) {
            $Helper = $this->getHelper('question');
            $question = new Question('<fg=green>Please enter the file size to generate (ie 5KB,5MB)</><fg=yellow>[5MB]:</>', '5MB');
            $question->setValidator(function ($answer) {
                if (!preg_match('/^\d+(kb|mb|gb)$/i', $answer)) {
                    throw new \RuntimeException(
                        'The value has to be one of those: [kB,MB,GB], ' . substr($answer, -2) . " given"
                    );
                }
                return $answer;
            });
            return $Helper->ask($input, $output, $question);
        }
        return $mOptionValue;
    }

    /**
     * Sprawdza poprawność wpisanego integera, jeżeli jest błędny prosi o porane właściwego
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param $sOptionName
     * @param Question $Question
     * @param null $mLtCompareValue wartosc od której podany integer musi być większy
     * @return mixed
     */
    protected function validateIntegerValue(InputInterface $input, OutputInterface $output, $sOptionName, Question $Question, $mLtCompareValue = null)
    {
        $mOptionValue = $input->getOption($sOptionName);
        if ((empty($mOptionValue) && $mOptionValue != 0) || !preg_match('/^\d+$/', $mOptionValue) || (!empty($mCompareValue) && $mOptionValue < $mCompareValue)) {
            $Helper = $this->getHelper('question');
            $question = $Question;
            $question->setValidator(function ($answer) use ($mLtCompareValue) {
                if (!preg_match('/^\d+$/', $answer)) {
                    throw new \RuntimeException(
                        'The value has to be integer , ' . gettype($answer) . " given"
                    );
                }
                if (!empty($mCompareValue) && $answer < $mLtCompareValue) {
                    throw new \RuntimeException(
                        'The value has to be gt :' . $mCompareValue
                    );
                }
                return $answer;
            });
            return $Helper->ask($input, $output, $question);
        }
        return $mOptionValue;
    }

}
