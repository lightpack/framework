# Security Policy

## Supported Versions

The following versions of Lightpack Framework are currently being supported with security updates:

| Version | Supported          |
| ------- | ------------------ |
| 0.9.x   | :white_check_mark: |
| < 0.9   | :x:                |

## Reporting a Vulnerability

We take the security of Lightpack Framework seriously. If you believe you have found a security vulnerability, please report it responsibly.

### Please DO NOT:

- Open a public issue on GitHub
- Post about the vulnerability on social media, forums, or mailing lists
- Include exploit code in any public communication

### Please DO:

1. **Report privately** through GitHub Security Advisories:
   - Go to [Security Advisories](https://github.com/lightpack/framework/security/advisories/new)
   - Click "New draft security advisory"
   - Fill in the details of the vulnerability

2. **Or email directly**:
   - Send an email to: pt21388@gmail.com
   - Subject line: `[SECURITY] Lightpack Framework - Brief description`
   - Include:
     - A clear description of the vulnerability
     - Steps to reproduce (if applicable)
     - The affected version(s)
     - Any potential impact assessment
     - Suggested fix (if you have one)

### Response Timeline

We aim to respond to security reports within **48 hours**.

- **Acknowledgment**: Within 48 hours of receiving the report
- **Investigation**: Within 7 days of acknowledgment
- **Fix and Release**: As soon as a fix is ready, typically within 30 days
- **Public Disclosure**: After a fix is released, we will publish a security advisory with full details and credit the reporter (unless they prefer to remain anonymous)

### Security Best Practices

For users of Lightpack Framework:

1. **Keep your framework updated** to the latest supported version
2. **Never commit sensitive credentials** (API keys, database passwords) to version control
3. **Use environment variables** for all sensitive configuration
4. **Validate all user input** using the framework's validation system
5. **Use prepared statements** (the framework's ORM does this automatically)
6. **Enable HTTPS** in production environments
7. **Keep PHP updated** to the latest stable version

## Security Updates

Security updates will be announced through:

- GitHub Security Advisories
- Release notes in CHANGELOG.md
- GitHub Releases

## Credits

We thank all security researchers and community members who responsibly disclose vulnerabilities to help keep Lightpack Framework secure.
