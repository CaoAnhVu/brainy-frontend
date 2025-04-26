# System Patterns

## Kiến trúc tổng thể

Brainy Frontend sử dụng kiến trúc component-based với React và Vite làm nền tảng chính. Dự án được tổ chức theo mô hình atomic design, phân tách rõ ràng giữa các thành phần giao diện, logic nghiệp vụ và quản lý trạng thái.

## Các mẫu thiết kế chính

- **Component Composition**: Sử dụng các thành phần nhỏ, tái sử dụng được để xây dựng UI phức tạp
- **Container/Presentational Pattern**: Tách biệt logic nghiệp vụ và hiển thị
- **Custom Hooks**: Tách logic phức tạp thành các hook có thể tái sử dụng
- **Context API**: Quản lý trạng thái ứng dụng toàn cục
- **Lazy Loading**: Tối ưu hiệu suất bằng cách chỉ tải các thành phần khi cần thiết

## Cấu trúc thư mục

```
src/
  ├── assets/         # Tài nguyên tĩnh (hình ảnh, fonts)
  ├── components/     # Các thành phần UI tái sử dụng
  │   ├── common/     # Các thành phần chung (Button, Card, etc.)
  │   ├── layout/     # Các thành phần layout (Header, Footer, Sidebar)
  │   └── [feature]/  # Các thành phần theo tính năng
  ├── context/        # Context API và providers
  ├── hooks/          # Custom hooks
  ├── pages/          # Các trang và route components
  ├── services/       # Tương tác API và các service khác
  └── utils/          # Các tiện ích và hàm helper
```

## Luồng dữ liệu

- RESTful API giao tiếp với backend thông qua Axios
- Quản lý state toàn cục bằng Context API
- Các component chỉ truy cập dữ liệu thông qua custom hooks và services

## Các quyết định kỹ thuật quan trọng

- Sử dụng Tailwind CSS cho styling để tăng tốc độ phát triển
- Áp dụng SSR (Server Side Rendering) cho SEO và hiệu suất trang đầu tiên
- Triển khai PWA (Progressive Web App) để hỗ trợ trải nghiệm offline
- Tuân thủ các tiêu chuẩn WCAG cho khả năng truy cập
