<p align="center">
  <img src="https://raw.githubusercontent.com/OpenSourceAfrica/branding/47f064aac7f9a970ab3849711acbc76804956af8/Full%20Logo%20Color/Full_Logo_Color.svg" alt="LacePHP">
</p>

<p align="center">
  <em>A delightful, shoe-themed PHP microframework<br>
  “Lace up your APIs faster than ever!”</em>
</p>

---

## What is LacePHP?

LacePHP is an **offline-first**, **API-first** PHP microframework with a playful, shoe-themed vocabulary:

- **Sockliner**: The application kernel
- **Shoepad**: Advanced bootstrap layer
- **ShoeResponder**: Flexible response formatter & error pages
- **Knots**: Middleware (MagicDebugKnots, RateLimitKnots, MetricsKnots, ShoeGateKnots…)
- **SewGet/SewPost**: Fluent route registration
- **ShoeDeploy**: Zero-dependency SSH-based deploy tool
- **ShoeGenie**: AI-powered API scaffolding (premium)
- **ShoeBuddy**: AI pair-programmer (premium)
- **ShoeHttp**: Lightweight cURL wrapper (REST, SOAP, multipart, auth)

Whether you’re building a quick prototype or a production REST API, LacePHP helps you “lace” things together in minutes—no boilerplate, no heavy dependencies.

---

# Contributing to LacePHP

Thank you for considering a contribution to LacePHP! Whether you want to fix a bug, add a feature, improve performance or enhance the documentation, your help makes the framework stronger.

## Why Contribute?

- **Improve** the tools you rely on.
- **Give back** to the community.
- **Learn** best practices in open-source PHP development.

## Getting Started

1. **Fork** the repository on GitHub:  
   https://github.com/OpenSourceAfrica/LacePHP
2. **Clone** your fork locally:
   ```bash
   git clone git@github.com:<your-username>/LacePHP.git
   cd LacePHP
   ```
3. **Install** dependencies and tools:
   ```bash
   composer install
   npm install       # if docs use a local build tool
   ```
4. **Create** a feature branch:
   ```bash
   git checkout -b feature/describe-your-change
   ```

---

## Code Contributions

### Coding Standards

- Follow **PSR-12** (indent with 4 spaces, camelCase for methods, StudlyCaps for classes).
- Keep methods short and focused (single responsibility).
- Add or update **unit tests** for any new logic (use `phpunit`).

### Branch & Commit

- Use descriptive branch names: `feature/add-blueprint-support`, `bugfix/offsetget-type`
- Write clear commit messages:
    - Title (50 characters max)
    - Blank line
    - Detailed description if necessary

### Testing

- Ensure **all tests pass** locally:
  ```bash
  vendor/bin/phpunit
  ```
- If you add new functionality, include tests under `tests/`.

### Pull Request

1. Push your branch to your fork:
   ```bash
   git push origin feature/describe-your-change
   ```
2. Create a **Pull Request** against the `main` branch of the upstream repo.
3. In your PR description, explain:
    - What problem you’re solving
    - How you tested it
    - Any backward-compatibility considerations
4. Address review feedback by updating your branch; the PR will update automatically.

---

## Documentation Contributions

Our docs live in **reStructuredText** under `manual/documentation/`. To improve them:

1. **Locate** the `.rst` file you wish to update (e.g. `routing.rst`, `models.rst`).
2. **Edit** or **add** content following the existing style (headings, code blocks).
3. **Build** the docs locally to preview (if you have Sphinx installed):
   ```bash
   pip install sphinx
   cd manual
   make html
   ```
   Then open `_build/html/index.html` in your browser.
4. **Test** that your changes render correctly, with no syntax errors.
5. **Commit**, push and open a PR as described above, referencing the documentation area.

---

## Reporting Security Vulnerabilities

If you uncover a security flaw in LacePHP, **please do not** share it in public forums or issue trackers. Instead, send a detailed report directly to **vulnerability@lacephp.com**.

Your report should include:
- **Description:** A concise explanation of the vulnerability
- **Reproduction Steps:** Exact steps or code needed to demonstrate the issue
- **Environment Details:** LacePHP version, PHP version, and any relevant configuration
- **Proof-of-Concept:** Sample code or commands that trigger the problem

If your finding is especially impactful and you’d like recognition, indicate in your email that we may credit you in our newsletter. Thank you for helping us keep LacePHP secure!

---

## Best Practices

- **One change per PR**: keep PRs focused.
- **Reference issues**: if your work addresses a GitHub issue, mention it (e.g. `Fixes #123`).
- **Be polite and patient**: maintainers are volunteers—reviews may take a little time.

## Code of Conduct

All contributors must adhere to our [Code of Conduct](https://github.com/OpenSourceAfrica/LacePHP/blob/main/CODE_OF_CONDUCT.md). Be respectful, inclusive and constructive in all communications.

Thank you for helping make LacePHP better!

---

## License

LacePHP is open-source software licensed under the **MIT License**.  
&copy; 2025 Akinyele Olubodun — [akinyeleolubodun.com](https://www.akinyeleolubodun.com) | [blog.akinyeleolubodun.com](https://blog.akinyeleolubodun.com)
