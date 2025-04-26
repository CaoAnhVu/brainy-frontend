import { useState } from 'react';
// eslint-disable-next-line
import { motion } from 'framer-motion';

const FlashcardItem = ({ flashcard, onComplete }) => {
  const [isFlipped, setIsFlipped] = useState(false);

  const handleFlip = () => {
    setIsFlipped(!isFlipped);
  };

  return (
    <div className="w-full max-w-md mx-auto h-80 perspective">
      <motion.div
        className={`relative w-full h-full transition-transform duration-500 transform-style-3d cursor-pointer ${isFlipped ? 'rotate-y-180' : ''}`}
        onClick={handleFlip}
        initial={false}
        animate={{ rotateY: isFlipped ? 180 : 0 }}
        transition={{ duration: 0.5 }}
      >
        {/* Front of card */}
        <div className={`absolute w-full h-full backface-hidden bg-white rounded-xl shadow-lg p-8 flex flex-col justify-between ${isFlipped ? 'invisible' : ''}`}>
          <div className="text-center">
            <h2 className="text-3xl font-bold text-gray-800">{flashcard.word}</h2>
            {flashcard.phonetic && <p className="text-gray-500 mt-2">{flashcard.phonetic}</p>}
            {flashcard.pos && <p className="text-primary-600 mt-1 font-medium">{flashcard.pos}</p>}
          </div>

          <div className="text-center text-gray-500">Click to flip</div>
        </div>

        {/* Back of card */}
        <div className={`absolute w-full h-full backface-hidden bg-white rounded-xl shadow-lg p-8 rotate-y-180 ${!isFlipped ? 'invisible' : ''}`}>
          <div className="flex flex-col h-full">
            <div className="flex-grow">
              <h3 className="text-xl font-medium text-gray-700 mb-4">Definition:</h3>
              <p className="text-gray-800">{flashcard.definition}</p>

              {flashcard.example && (
                <div className="mt-4">
                  <h3 className="text-xl font-medium text-gray-700 mb-2">Example:</h3>
                  <p className="text-gray-600 italic">{flashcard.example}</p>
                </div>
              )}
            </div>

            <div className="mt-6">
              <h3 className="text-sm font-medium text-gray-700 mb-2">How well did you know this?</h3>
              <div className="flex justify-between">
                <button
                  onClick={(e) => {
                    e.stopPropagation();
                    onComplete(1);
                  }}
                  className="px-3 py-1 bg-red-500 text-white rounded-md text-sm"
                >
                  Again
                </button>
                <button
                  onClick={(e) => {
                    e.stopPropagation();
                    onComplete(3);
                  }}
                  className="px-3 py-1 bg-yellow-500 text-white rounded-md text-sm"
                >
                  Good
                </button>
                <button
                  onClick={(e) => {
                    e.stopPropagation();
                    onComplete(5);
                  }}
                  className="px-3 py-1 bg-green-500 text-white rounded-md text-sm"
                >
                  Easy
                </button>
              </div>
            </div>
          </div>
        </div>
      </motion.div>
    </div>
  );
};

export default FlashcardItem;
