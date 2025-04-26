import React, { useState } from 'react';
import Button from '../common/Button';

const QuizQuestion = ({ question, options, correctAnswer, onAnswer }) => {
  const [selectedOption, setSelectedOption] = useState(null);
  const [isAnswered, setIsAnswered] = useState(false);

  const handleOptionSelect = (option) => {
    if (isAnswered) return;
    setSelectedOption(option);
  };

  const handleSubmit = () => {
    if (!selectedOption || isAnswered) return;

    setIsAnswered(true);
    const isCorrect = selectedOption === correctAnswer;

    // Wait a bit to show the result before moving to next question
    setTimeout(() => {
      onAnswer(isCorrect);
      setSelectedOption(null);
      setIsAnswered(false);
    }, 1500);
  };

  const getOptionClass = (option) => {
    if (!isAnswered) {
      return selectedOption === option ? 'border-primary-500 bg-primary-50' : 'border-gray-200 hover:border-primary-300 hover:bg-gray-50';
    }

    if (option === correctAnswer) {
      return 'border-green-500 bg-green-50';
    }

    if (option === selectedOption) {
      return 'border-red-500 bg-red-50';
    }

    return 'border-gray-200 opacity-50';
  };

  return (
    <div className="max-w-2xl mx-auto">
      <div className="mb-6">
        <h3 className="text-xl font-medium text-gray-800 mb-2">{question}</h3>
      </div>

      <div className="space-y-3 mb-6">
        {options.map((option, index) => (
          <div key={index} className={`flex items-center p-4 border-2 rounded-lg cursor-pointer transition-colors ${getOptionClass(option)}`} onClick={() => handleOptionSelect(option)}>
            <div className="flex-1">
              <p className="text-gray-700">{option}</p>
            </div>

            {isAnswered && option === correctAnswer && (
              <span className="text-green-500">
                <svg xmlns="http://www.w3.org/2000/svg" className="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
                </svg>
              </span>
            )}

            {isAnswered && option === selectedOption && option !== correctAnswer && (
              <span className="text-red-500">
                <svg xmlns="http://www.w3.org/2000/svg" className="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                </svg>
              </span>
            )}
          </div>
        ))}
      </div>

      <div className="flex justify-end">
        <Button onClick={handleSubmit} disabled={!selectedOption || isAnswered}>
          {isAnswered ? 'Next Question...' : 'Check Answer'}
        </Button>
      </div>
    </div>
  );
};

export default QuizQuestion;
