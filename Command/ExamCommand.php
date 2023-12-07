<?php

namespace Exam\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Yaml\Yaml;

#[AsCommand(
    name: 'start',
    description: 'start new Exam Session',
    hidden: false,
)]
class ExamCommand extends Command
{
    const DATA_PATH = __DIR__ . '/../data';

    protected function configure()
    {
        $this->addArgument('nb', InputArgument::OPTIONAL, 'question number default 50');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $yml = scandir(self::DATA_PATH);

        $questions = [];

        foreach ($yml as $file) {
            if (str_ends_with($file, '.yml')) {
                $data = Yaml::parse(file_get_contents(self::DATA_PATH . '/' . $file));
                $questions = array_merge($data['questions'], $questions);
            }
        }
        if (empty($questions)) {
            $output->writeln('empty questions Data');

            return 0;
        }

        $nbQuestions = ($input->getArgument('nb') ?? 25);

        $count = count($questions);

        if ($nbQuestions >= $count) {
            $nbQuestions = $count - 1;
        }

        $arrayRand = array_rand($questions, $nbQuestions);

        $examQuestions = [];
        if (is_array($arrayRand)) {
            foreach ($arrayRand as $rand) {
                $examQuestions[] = $questions[$rand];
            }
        } else {
            $examQuestions[] = $questions[$arrayRand];
        }

        $answers = [];
        $helper = $this->getHelper('question');

        foreach ($examQuestions as $question) {
            $this->resortQuestion($question);
            while (true) {
                $this->showQuestion($question, $output);

                $inputQuestion = new Question('Please choose the right proposition:');

                $nbAnswer = count($question['answers']);

                $res = $helper->ask($input, $output, $inputQuestion);

                if ($this->correctInput($res, $nbAnswer)) {
                    break;
                } else {
                    $output->writeln('<fg=red> Wrong Input</>');
                }
            }
            $answers[] = ['answer' => $res, 'question' => $question];
        }

        $this->showResult($answers, $output, $nbQuestions);

        return 0;
    }

    private function resortQuestion(array &$question)
    {
        $answers = $question['answers'];
        shuffle($answers);
        $question['answers'] = $answers;
    }

    private function showQuestion(array $question, OutputInterface $output)
    {
        $output->writeln('<fg=green> Question:' . $question['question'] . '</>');
        $output->writeln('<fg=blue> Help:' . ($question['help'] ?? '') . '</>');

        foreach ($question['answers'] as $i => $answer) {
            $output->writeln(($i + 1) . ') ' . $answer['value']);
        }
    }

    protected function correctInput(?string $res, int $size): bool
    {
        if (is_null($res)) {
            return false;
        }
        $input = explode(',', $res);

        foreach ($input as $i) {
            if (!is_numeric($i) || $i > $size || $i < 1) {
                return false;
            }
        }

        return true;
    }

    private function showResult(array $answers, OutputInterface $output, int $nbQuestions)
    {
        $score = 0;

        $output->writeln("Result:");

        foreach ($answers as $answer) {
            $output->writeln('<fg=green> Question:' . $answer['question']['question'] . '</>');
            $input = array_map(fn($el) => intval($el), explode(',', $answer['answer']));
            $correct = true;

            foreach ($answer['question']['answers'] as $i => $res) {
                if ($res['correct']) {
                    $r = '';

                    if (!in_array($i + 1, $input)) {
                        $correct = false;
                    }else{
                        $r = '(♣)';
                    }

                    $output->writeln('<fg=green>' . ($i+1) . '):' . $res['value'] . $r . '</>');
                } else {
                    $r = '';

                    if (in_array($i + 1, $input)) {
                        $r = '(‡┐)';
                    }
                    $output->writeln('<fg=red>' . ($i+1) . '):' . $res['value'] . $r . '</>');
                }
            }
            $output->writeln('<fg=blue> you answer was:' . $answer['answer'] . '</>');
            
            if ($correct) {
                $score++;
            }
        }

        $output->writeln("Final Score: $score/$nbQuestions");
    }
}