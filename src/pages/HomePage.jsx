import { Link } from 'react-router-dom';

const HomePage = () => {
  return (
    <div className="w-full bg-gray-100">
      <section className="w-full bg-gray-900 text-white py-20">
        <div className="container mx-auto px-4 text-center">
          <h1 className="text-4xl md:text-5xl font-bold mb-6 text-blue-500">Learn Vocabulary with Brainy</h1>
          <p className="text-xl mb-8">Master new words efficiently using our spaced repetition flashcard system</p>
          <div className="flex flex-wrap justify-center gap-4">
            <a href="/flashcards" className="btn bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg">
              Start Learning
            </a>
            <a href="/topics" className="btn bg-white text-gray-800 hover:bg-gray-100 px-6 py-3 rounded-lg">
              Browse Topics
            </a>
          </div>
        </div>
      </section>

      <section className="py-16 bg-white">
        <div className="container mx-auto px-4">
          <h2 className="text-3xl font-bold text-center mb-12">How Brainy Works</h2>
          <p className="text-center text-lg mb-12">Our scientifically-proven approach helps you learn faster and remember longer</p>

          <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div className="card text-center">
              <div className="rounded-full bg-primary-100 w-16 h-16 flex items-center justify-center mx-auto mb-4">
                <span className="text-primary-600 text-2xl font-bold">1</span>
              </div>
              <h3 className="text-xl font-bold mb-2">Select Topics</h3>
              <p className="text-gray-600">Choose from our extensive library of vocabulary topics</p>
            </div>

            <div className="card text-center">
              <div className="rounded-full bg-primary-100 w-16 h-16 flex items-center justify-center mx-auto mb-4">
                <span className="text-primary-600 text-2xl font-bold">2</span>
              </div>
              <h3 className="text-xl font-bold mb-2">Review Flashcards</h3>
              <p className="text-gray-600">Practice with interactive flashcards and rate your confidence</p>
            </div>

            <div className="card text-center">
              <div className="rounded-full bg-primary-100 w-16 h-16 flex items-center justify-center mx-auto mb-4">
                <span className="text-primary-600 text-2xl font-bold">3</span>
              </div>
              <h3 className="text-xl font-bold mb-2">Master Vocabulary</h3>
              <p className="text-gray-600">Our system schedules reviews at optimal intervals for long-term retention</p>
            </div>
          </div>
        </div>
      </section>
    </div>
  );
};

export default HomePage;
