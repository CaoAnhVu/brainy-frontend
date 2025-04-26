import React, { useState, useEffect } from 'react';
import { useQuery, useMutation } from '@tanstack/react-query';
import { quizService } from '../services/quizService';
import QuizTopicSelector from '../components/quiz/QuizTopicSelector';
import QuizQuestion from '../components/quiz/QuizQuestion';
import QuizResult from '../components/quiz/QuizResult';
import Spinner from '../components/common/Spinner';
import EmptyState from '../components/common/EmptyState';

const QuizPage = () => {
  const [selectedTopic, setSelectedTopic] = useState(null);
  const [currentQuestionIndex, setCurrentQuestionIndex] = useState(0);
  const [score, setScore] = useState(0);
  const [isQuizComplete, setIsQuizComplete] = useState(false);
  const [timeSpent, setTimeSpent] = useState(0);
  const [timerInterval, setTimerInterval] = useState(null);

  // Fetch quiz questions when topic is selected
  const {
    data: quizData,
    isLoading,
    error,
    refetch,
  } = useQuery({
    queryKey: ['quiz', selectedTopic],
    queryFn: () => quizService.getQuizByTopic(selectedTopic),
    enabled: !!selectedTopic,
  });

  // Save quiz result mutation
  const saveResultMutation = useMutation({
    mutationFn: ({ userId, topic, score, totalQuestions, timeSpent }) => quizService.saveQuizResult(userId, topic, score, totalQuestions, timeSpent),
  });

  // Start timer when quiz begins
  useEffect(() => {
    if (selectedTopic && !isQuizComplete) {
      const interval = setInterval(() => {
        setTimeSpent((prev) => prev + 1);
      }, 1000);

      setTimerInterval(interval);

      return () => clearInterval(interval);
    }
  }, [selectedTopic, isQuizComplete]);

  // Handle topic selection
  const handleSelectTopic = (topic) => {
    setSelectedTopic(topic);
    setCurrentQuestionIndex(0);
    setScore(0);
    setIsQuizComplete(false);
    setTimeSpent(0);
  };

  // Handle answer submission
  const handleAnswer = (isCorrect) => {
    if (isCorrect) {
      setScore((prevScore) => prevScore + 1);
    }

    const questions = quizData?.data || [];

    if (currentQuestionIndex < questions.length - 1) {
      setCurrentQuestionIndex((prevIndex) => prevIndex + 1);
    } else {
      // Quiz is complete
      clearInterval(timerInterval);
      setIsQuizComplete(true);

      // Save result
      const userId = 'default-user-id'; // Replace with actual user ID
      saveResultMutation.mutate({
        userId,
        topic: selectedTopic,
        score,
        totalQuestions: questions.length,
        timeSpent,
      });
    }
  };

  // Handle retry
  const handleRetry = () => {
    setCurrentQuestionIndex(0);
    setScore(0);
    setIsQuizComplete(false);
    setTimeSpent(0);
    refetch();
  };

  // Handle back to topics
  const handleBackToTopics = () => {
    setSelectedTopic(null);
    setCurrentQuestionIndex(0);
    setScore(0);
    setIsQuizComplete(false);
    setTimeSpent(0);
  };

  // Render quiz content based on state
  const renderQuizContent = () => {
    if (!selectedTopic) {
      return <QuizTopicSelector onSelectTopic={handleSelectTopic} />;
    }

    if (isLoading) {
      return <Spinner className="py-12" size="lg" />;
    }

    if (error) {
      return <EmptyState title="Failed to load quiz" description="There was an error loading the quiz questions. Please try again." actionText="Retry" onAction={refetch} />;
    }

    const questions = quizData?.data || [];

    if (questions.length === 0) {
      return <EmptyState title="No questions available" description="There are no quiz questions available for this topic." actionText="Choose another topic" onAction={handleBackToTopics} />;
    }

    if (isQuizComplete) {
      return <QuizResult score={score} totalQuestions={questions.length} timeSpent={timeSpent} onRetry={handleRetry} onBackToTopics={handleBackToTopics} />;
    }

    const currentQuestion = questions[currentQuestionIndex];

    return (
      <div>
        <div className="mb-6 flex justify-between items-center">
          <div>
            <h2 className="text-xl font-semibold">Quiz: {selectedTopic}</h2>
            <p className="text-gray-600">
              Question {currentQuestionIndex + 1} of {questions.length}
            </p>
          </div>
          <div className="text-gray-600">
            Time: {Math.floor(timeSpent / 60)}m {timeSpent % 60}s
          </div>
        </div>

        <QuizQuestion question={currentQuestion.question} options={currentQuestion.options} correctAnswer={currentQuestion.correctAnswer} onAnswer={handleAnswer} />
      </div>
    );
  };

  return (
    <div>
      <section className="mb-8">
        <h1 className="text-3xl font-bold mb-2">Vocabulary Quiz</h1>
        <p className="text-gray-600">Test your knowledge with interactive quizzes</p>
      </section>

      {renderQuizContent()}
    </div>
  );
};

export default QuizPage;
