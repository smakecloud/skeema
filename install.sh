#!/bin/bash

# Default versions
SKEEMA_VERSION=${SKEEMA_VERSION:-1.10.1}
GH_OST_VERSION=${GH_OST_VERSION:-1.1.5}

# Function to check if a command exists
command_exists() {
    type "$1" &> /dev/null
}

# Function to check Skeema version
check_skeema_version() {
    if command_exists skeema; then
        # Extracts the version and trims the '-community,' part
        INSTALLED_VERSION=$(skeema version | grep 'skeema version' | awk '{print $3}' | sed 's/-community,.*//')
        if [ "$INSTALLED_VERSION" == "$SKEEMA_VERSION" ]; then
            return 0
        fi
    fi
    return 1
}

# Function to check gh-ost version
check_gh_ost_version() {
    if command_exists gh-ost; then
        INSTALLED_VERSION=$(gh-ost --version)
        if [ "$INSTALLED_VERSION" == "$GH_OST_VERSION" ]; then
            return 0
        fi
    fi
    return 1
}

echo "Checking for existing installations..."

# Determine OS and architecture
OS="$(uname)"
ARCH="$(uname -m)"
OS_LOWERCASE=$(echo "$OS" | tr '[:upper:]' '[:lower:]')

# Correcting the OS name for macOS in the URL
if [ "$OS" = "Darwin" ]; then
    OS_URL="mac"
else
    OS_URL=$OS_LOWERCASE
fi

# Install Skeema if not already installed or version mismatch
if ! check_skeema_version; then
    echo "Installing Skeema (v$SKEEMA_VERSION)..."
    if [ "$OS" = "Linux" ] || [ "$OS" = "Darwin" ]; then
        if [ "$ARCH" = "x86_64" ]; then
            ARCH_SUFFIX="_amd64"
        elif [ "$ARCH" = "arm64" ] || [ "$ARCH" = "aarch64" ]; then
            ARCH_SUFFIX="_arm64"
        else
            echo "Unsupported architecture: $ARCH"
            exit 1
        fi

        SKEEMA_URL="https://github.com/skeema/skeema/releases/download/v${SKEEMA_VERSION}/skeema_${SKEEMA_VERSION}_${OS_URL}${ARCH_SUFFIX}.tar.gz"
        echo "Downloading Skeema from $SKEEMA_URL..."
        curl -LO $SKEEMA_URL
        if [ -f "skeema_${SKEEMA_VERSION}_${OS_URL}${ARCH_SUFFIX}.tar.gz" ]; then
            tar -xzvf skeema_${SKEEMA_VERSION}_${OS_URL}${ARCH_SUFFIX}.tar.gz skeema
            rm skeema_${SKEEMA_VERSION}_${OS_URL}${ARCH_SUFFIX}.tar.gz
            sudo mv skeema /usr/local/bin/
        else
            echo "Failed to download Skeema."
            exit 1
        fi
    else
        echo "Unsupported operating system: $OS"
        exit 1
    fi
else
    echo "Skeema is already installed and up to date."
fi

# Install gh-ost if not already installed or version mismatch
if ! check_gh_ost_version; then
    echo "Installing gh-ost (v$GH_OST_VERSION)..."
    if [ "$OS" = "Linux" ] || [ "$OS" = "Darwin" ]; then
        GH_OST_URL="https://github.com/github/gh-ost/releases/download/v${GH_OST_VERSION}/gh-ost-binary-${OS_LOWERCASE}${ARCH_SUFFIX}-${GH_OST_VERSION}.tar.gz"
        echo "Downloading gh-ost from $GH_OST_URL..."
        curl -LO $GH_OST_URL
        if [ -f "gh-ost-binary-${OS_LOWERCASE}${ARCH_SUFFIX}-${GH_OST_VERSION}.tar.gz" ]; then
            tar -xzvf gh-ost-binary-${OS_LOWERCASE}${ARCH_SUFFIX}-${GH_OST_VERSION}.tar.gz gh-ost
            rm gh-ost-binary-${OS_LOWERCASE}${ARCH_SUFFIX}-${GH_OST_VERSION}.tar.gz
            sudo mv gh-ost /usr/local/bin/
        else
            echo "Failed to download gh-ost."
            exit 1
        fi
    fi
else
    echo "gh-ost is already installed and up to date."
fi

echo "Installation completed."
