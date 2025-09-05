# Contributing to Laravel Ownership

Thank you for considering contributing to **Laravel Ownership**! 🎉  
Contributions, issues, and feature requests are welcome.

---

## 🛠 How to Contribute

### 1. Fork & Clone
- Fork the repo from GitHub.
- Clone your fork locally:
  ```bash
  git clone https://github.com/dibakarmitra/laravel-ownership.git
  cd laravel-ownership
  ```

### 2. Install Dependencies
```bash
composer install
```

### 3. Run Tests
We use **PHPUnit with Orchestra Testbench** to test the package.

```bash
vendor/bin/phpunit
```

Make sure all tests pass before opening a PR.

### 4. Coding Standards
- Follow **PSR-12** coding style.
- Use **strict types** (`declare(strict_types=1);`).
- Keep method and variable names descriptive.
- Add PHPDoc where necessary.

You can run code style checks with:
```bash
composer lint
```
*(Add PHP-CS-Fixer or Laravel Pint if you want auto-fix support.)*

### 5. Create a Branch
Create a feature or bugfix branch:
```bash
git checkout -b feature/add-new-functionality
```

### 6. Commit Guidelines
- Use clear, descriptive commit messages.
- Example:
  ```
  feat: add support for syncing multiple owners
  fix: resolve issue with ownership deletion in soft deletes
  docs: improve usage examples in README
  ```

### 7. Submit Pull Request
- Push your branch:
  ```bash
  git push origin feature/add-new-functionality
  ```
- Open a PR on GitHub against the `main` branch.

---

## 🧪 Testing
We encourage **Test-Driven Development (TDD)**.  
If you add new functionality, please include unit tests to cover it.

---

## 📜 Code of Conduct
By contributing, you agree to follow our [Code of Conduct](CODE_OF_CONDUCT.md) (if available).

---

## 🙌 Thank You
Your contributions make this package better for everyone. ❤️
