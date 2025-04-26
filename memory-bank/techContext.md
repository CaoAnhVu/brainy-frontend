# Technical Context

## Tech Stack

- **Frontend Framework**: React 18 với Vite
- **UI Library**: Tailwind CSS
- **State Management**: React Context API, Zustand
- **Form Handling**: React Hook Form
- **API Client**: Axios
- **Testing**: Jest, React Testing Library
- **Authentication**: JWT
- **Build/Deploy**: GitHub Actions, Vercel

## Development Environment

- **Node.js**: v18.x+
- **Package Manager**: npm
- **Linting**: ESLint
- **Formatting**: Prettier
- **Version Control**: Git, GitHub
- **IDE**: VS Code với các extension được khuyến nghị

## API Integration

- RESTful API endpoints từ Brainy Backend
- Giao tiếp qua HTTPS với authentication thông qua Bearer token
- Gọi API thông qua các service được tập trung và custom hooks

## Dependencies chính

- react-router-dom: Routing
- react-query: Data fetching và cache
- tailwindcss: Styling
- axios: HTTP client
- dayjs: Xử lý thời gian
- react-hook-form: Xử lý form
- chart.js: Trực quan hóa dữ liệu
- framer-motion: Animation

## Performance Considerations

- Code splitting và lazy loading
- Optimized production builds
- Image optimization
- Memoization (React.memo, useMemo, useCallback)
- Effective use of React key prop
- Virtual scrolling cho danh sách dài

## Security Best Practices

- Sanitize user inputs
- Prevent XSS attacks
- CSRF protection
- Secure storage of sensitive data
- Access control theo role-based permissions
