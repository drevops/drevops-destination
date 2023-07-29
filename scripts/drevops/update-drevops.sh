#!/usr/bin/env bash
##
# Update DrevOps.
#

t=$(mktemp) && export -p >"$t" && set -a && . ./.env && if [ -f ./.env.local ]; then . ./.env.local; fi && set +a && . "$t" && rm "$t" && unset t

set -eu
[ -n "${DREVOPS_DEBUG:-}" ] && set -x

# Re-export variables only from .env to ignore any local overrides in .env.local.
# shellcheck disable=SC1091
set -a && . ./.env && set +a

# The URL of the installer script.
DREVOPS_INSTALLER_URL="${DREVOPS_INSTALLER_URL:-https://install.drevops.com}"

# Allow providing custom DrevOps commit hash to download the sources from.
DREVOPS_INSTALL_COMMIT="${DREVOPS_INSTALL_COMMIT:-${1:-}}"

# ------------------------------------------------------------------------------

export DREVOPS_INSTALLER_URL
export DREVOPS_INSTALL_COMMIT

curl -L "${DREVOPS_INSTALLER_URL}"?"$(date +%s)" >/tmp/install.php
php /tmp/install.php --quiet
rm /tmp/install.php >/dev/null