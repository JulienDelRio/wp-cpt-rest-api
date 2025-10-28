# Security Model & Best Practices

## Overview

The Custom Post Types REST API plugin uses a **binary API key authentication model** designed for external API integration scenarios (mobile apps, third-party services, integrations).

## API Key Access Model

### What API Keys Can Do

Valid API keys grant **full access** to all enabled Custom Post Types:

- ✅ **Read**: List and retrieve all posts from enabled CPTs
- ✅ **Create**: Add new posts to enabled CPTs
- ✅ **Update**: Modify existing posts in enabled CPTs
- ✅ **Delete**: Permanently delete posts from enabled CPTs
- ✅ **Metadata**: Full access to custom fields and post meta

### What API Keys Cannot Do

- ❌ Access WordPress admin interface
- ❌ Access non-enabled CPTs
- ❌ Access private meta fields (starting with `_`)
- ❌ Modify plugin settings or generate other keys
- ❌ Access standard WordPress posts/pages (only Custom Post Types)

## Why This Model?

### Design Rationale

This plugin is designed for **external API consumers** that need programmatic access:

- Mobile applications
- Third-party integrations
- External services and APIs
- Automation tools
- Data synchronization systems

For these use cases, binary access (all or nothing) is:
- ✅ **Simple**: Easy to understand and implement
- ✅ **Standard**: Common pattern in API platforms (Stripe, GitHub, AWS)
- ✅ **Appropriate**: External services typically need full CRUD access
- ✅ **Secure**: Admin controls which CPTs are exposed

### When This Model Works Well

- External application integration
- Trusted third-party services
- Internal automation tools
- Data synchronization
- Mobile app backends

### When You Might Need More

If you need granular permissions (read-only keys, per-CPT restrictions), consider:
- Implementing a custom capability system
- Using WordPress user accounts with application passwords
- Adding a middleware layer with additional access controls

## Security Best Practices

### Key Generation

1. **Descriptive Labels**: Add descriptions when generating keys
   - "Production Mobile App"
   - "Development Testing"
   - "Customer Portal Integration"

2. **Separate Keys**: Generate different keys for:
   - Each environment (dev, staging, production)
   - Each application or service
   - Each integration partner

### Key Storage

```bash
# ✅ GOOD: Environment variables
export WP_API_KEY="abc123xyz..."

# ✅ GOOD: .env files (excluded from git)
WP_API_KEY=abc123xyz...

# ❌ BAD: Hardcoded in source
const apiKey = "abc123xyz...";  // Never do this!

# ❌ BAD: Committed to git
# API Key: abc123xyz...  // Never commit keys!
```

### Key Rotation

**Regular Rotation Schedule:**
- Production keys: Every 90 days
- Staging keys: Every 180 days
- Development keys: As needed

**Immediate Rotation When:**
- Employee with access leaves
- Key suspected to be compromised
- Service/app is decommissioned
- Security incident occurs

### Access Control

**WordPress Admin Settings:**

1. **Enable Only Needed CPTs**
   - Navigate to Settings > CPT REST API
   - Enable only the CPTs that external services need
   - Regularly audit and disable unused CPTs

2. **Monitor Key Usage** (if logging enabled)
   - Review which keys are being used
   - Identify and remove unused keys
   - Investigate suspicious patterns

### Network Security

**Additional Security Layers:**

```nginx
# Nginx: Rate limiting
limit_req_zone $binary_remote_addr zone=api:10m rate=10r/s;

location /wp-json/cpt/ {
    limit_req zone=api burst=20 nodelay;
}
```

```apache
# Apache: IP whitelisting (optional)
<Location /wp-json/cpt/>
    Require ip 192.168.1.0/24
    Require ip 10.0.0.0/8
</Location>
```

## Incident Response

### If a Key is Compromised

**Immediate Actions:**

1. **Revoke the Key**
   - Go to Settings > CPT REST API
   - Delete the compromised key immediately

2. **Generate New Key**
   - Create a new key with a different value
   - Update all services using the old key

3. **Audit Activity**
   - Review recent API activity
   - Check for unauthorized changes
   - Restore any compromised data

4. **Review Access**
   - Verify which CPTs are enabled
   - Consider temporarily disabling API access
   - Review other active keys

### Prevention

- Never commit keys to version control
- Use secret management tools (Vault, AWS Secrets Manager)
- Implement monitoring and alerting
- Regular security audits
- Principle of least privilege (enable only needed CPTs)

## Compliance Considerations

### Data Privacy

- **GDPR/CCPA**: API access may expose personal data
- **Audit Trails**: Consider logging API operations
- **Data Minimization**: Enable only necessary CPTs
- **Access Records**: Keep records of who has API keys

### Industry Standards

This implementation follows common API security patterns:
- Bearer token authentication (RFC 6750)
- HTTPS required for production
- Constant-time key comparison (timing attack prevention)
- Secure random key generation

## Future Enhancements

While the current binary model is appropriate for most use cases, future versions may add:

- **Granular Permissions**: Per-key capabilities (read-only, specific CPTs)
- **Rate Limiting**: Built-in request throttling
- **Usage Analytics**: API call tracking and reporting
- **Key Expiration**: Automatic key expiration dates
- **IP Whitelisting**: Restrict keys to specific IPs
- **Webhook Support**: Real-time notifications for API events

## Questions?

For security concerns or questions about the API key model:
- Review the plugin documentation: `API_ENDPOINTS.md`
- Check the development guide: `CLAUDE.md`
- Report security issues: [GitHub Issues](https://github.com/JulienDelRio/wp-cpt-rest-api/issues)

---

**Remember**: This security model prioritizes simplicity and appropriateness for external API integration. Always implement additional security layers (HTTPS, rate limiting, monitoring) in production environments.
