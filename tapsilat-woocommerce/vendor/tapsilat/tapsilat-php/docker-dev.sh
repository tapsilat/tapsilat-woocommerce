#!/bin/bash

# Tapsilat PHP Package Development Script

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Function to show usage
show_usage() {
    echo "Usage: $0 [COMMAND]"
    echo ""
    echo "Commands:"
    echo "  build     - Build the Docker image"
    echo "  start     - Start the development container"
    echo "  stop      - Stop the development container"
    echo "  restart   - Restart the development container"
    echo "  shell     - Open a shell in the development container"
    echo "  test      - Run tests in the container"
    echo "  install   - Install dependencies"
    echo "  clean     - Clean up containers and images"
    echo "  help      - Show this help message"
    echo ""
    echo "Examples:"
    echo "  $0 build"
    echo "  $0 start"
    echo "  $0 shell"
    echo "  $0 test"
}

# Function to build the image
build_image() {
    print_status "Building Docker image..."
    docker-compose build
    print_success "Docker image built successfully!"
}

# Function to start the container
start_container() {
    print_status "Starting development container..."
    docker-compose up -d tapsilat-dev
    print_success "Development container started!"
    print_status "Container name: tapsilat-php-dev"
    print_status "Use '$0 shell' to access the container"
}

# Function to stop the container
stop_container() {
    print_status "Stopping development container..."
    docker-compose down
    print_success "Development container stopped!"
}

# Function to restart the container
restart_container() {
    print_status "Restarting development container..."
    docker-compose restart tapsilat-dev
    print_success "Development container restarted!"
}

# Function to open a shell
open_shell() {
    print_status "Opening shell in development container..."
    docker-compose exec tapsilat-dev bash
}

# Function to run tests
run_tests() {
    print_status "Running tests..."
    docker-compose run --rm tapsilat-test
}

# Function to install dependencies
install_deps() {
    print_status "Installing dependencies..."
    docker-compose exec tapsilat-dev composer install
    print_success "Dependencies installed!"
}

# Function to clean up
clean_up() {
    print_warning "This will remove all containers and images. Are you sure? (y/N)"
    read -r response
    if [[ "$response" =~ ^([yY][eE][sS]|[yY])$ ]]; then
        print_status "Cleaning up containers and images..."
        docker-compose down --rmi all --volumes --remove-orphans
        print_success "Cleanup completed!"
    else
        print_status "Cleanup cancelled."
    fi
}

# Main script logic
case "${1:-help}" in
    build)
        build_image
        ;;
    start)
        start_container
        ;;
    stop)
        stop_container
        ;;
    restart)
        restart_container
        ;;
    shell)
        open_shell
        ;;
    test)
        run_tests
        ;;
    install)
        install_deps
        ;;
    clean)
        clean_up
        ;;
    help|--help|-h)
        show_usage
        ;;
    *)
        print_error "Unknown command: $1"
        echo ""
        show_usage
        exit 1
        ;;
esac 