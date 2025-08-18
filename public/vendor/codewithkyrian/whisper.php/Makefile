# Makefile for building Whisper.cpp libraries for Whisper.php
# Supports Linux (x64, arm64), macOS (x64, arm64)
# Optional builds with CUDA, CoreML, OpenVINO support and AVX

# Build configuration
BUILD_TYPE ?= Release
CMAKE ?= cmake
BUILD_DIR ?= build
RUNTIME_DIR ?= runtimes
WHISPER_CPP_DIR ?= whisper.cpp

# Platforms and architectures
LINUX_ARCHS := x64 aarch64
MACOS_ARCHS := x86_64 arm64
WINDOWS_ARCHS := x64

# AVX support flags
AVX_FLAGS := -DGGML_AVX=ON -DGGML_AVX2=ON -DGGML_FMA=ON -DGGML_F16C=ON
NO_AVX_FLAGS := -DGGML_AVX=OFF -DGGML_AVX2=OFF -DGGML_FMA=OFF -DGGML_F16C=OFF

# Common CMake parameters
CMAKE_COMMON_PARAMS := -DCMAKE_BUILD_TYPE=$(BUILD_TYPE)

define select_linux_compiler
    $(eval ARCH := $(1))
    $(if $(filter x64,$(ARCH)),\
        $(eval CC := gcc)\
        $(eval CXX := g++)\
        $(eval CMAKE_ARCH_FLAGS := -DCMAKE_SYSTEM_PROCESSOR=x86_64),\
    $(if $(filter aarch64,$(ARCH)),\
        $(eval CC := aarch64-linux-gnu-gcc)\
        $(eval CXX := aarch64-linux-gnu-g++)\
        $(eval CMAKE_ARCH_FLAGS := -DCMAKE_SYSTEM_PROCESSOR=aarch64)\
        $(eval EXTRA_FLAGS += -DCMAKE_TOOLCHAIN_FILE=$(CURDIR)/aarch64-toolchain.cmake),\
        $(error Unsupported architecture: $(ARCH))))
endef

# Create toolchain file for aarch64 cross-compilation
define create_aarch64_toolchain
    echo '# Toolchain file for aarch64 cross-compilation' > aarch64-toolchain.cmake
    echo 'set(CMAKE_SYSTEM_NAME Linux)' >> aarch64-toolchain.cmake
    echo 'set(CMAKE_SYSTEM_PROCESSOR aarch64)' >> aarch64-toolchain.cmake
    echo 'set(CMAKE_C_COMPILER aarch64-linux-gnu-gcc)' >> aarch64-toolchain.cmake
    echo 'set(CMAKE_CXX_COMPILER aarch64-linux-gnu-g++)' >> aarch64-toolchain.cmake
    echo 'set(CMAKE_FIND_ROOT_PATH /usr/aarch64-linux-gnu)' >> aarch64-toolchain.cmake
    echo 'set(CMAKE_FIND_ROOT_PATH_MODE_PROGRAM NEVER)' >> aarch64-toolchain.cmake
    echo 'set(CMAKE_FIND_ROOT_PATH_MODE_LIBRARY ONLY)' >> aarch64-toolchain.cmake
    echo 'set(CMAKE_FIND_ROOT_PATH_MODE_INCLUDE ONLY)' >> aarch64-toolchain.cmake
endef

# Linux base build template
define linux_build
	$(eval ARCH := $(1))
	$(call select_linux_compiler,$(1))
	$(eval BUILD_PATH := $(BUILD_DIR)/linux-$(ARCH)$(2))
	$(eval EXTRA_FLAGS := $(3))
	$(if $(filter aarch64,$(ARCH)),$(call create_aarch64_toolchain))

	rm -rf $(BUILD_PATH)

	CC=$(CC) CXX=$(CXX) $(CMAKE) -S $(WHISPER_CPP_DIR) -B $(BUILD_PATH) -DCMAKE_SYSTEM_NAME=Linux $(CMAKE_ARCH_FLAGS) $(CMAKE_COMMON_PARAMS) $(EXTRA_FLAGS)
 	CC=$(CC) CXX=$(CXX) $(CMAKE) --build $(BUILD_PATH)

	mkdir -p $(RUNTIME_DIR)/linux-$(ARCH)$(2)
	cp $(BUILD_PATH)/src/libwhisper.so $(RUNTIME_DIR)/linux-$(ARCH)$(2)/
	cp $(BUILD_PATH)/ggml/src/libggml.so $(RUNTIME_DIR)/linux-$(ARCH)$(2)/

	patchelf --set-rpath '$$ORIGIN' $(RUNTIME_DIR)/linux-$(ARCH)$(2)/libwhisper.so
endef

# MacOS base build template
define macos_build
	$(eval ARCH := $(1))
	$(eval BUILD_PATH := $(BUILD_DIR)/macos-$(ARCH)$(2))
	$(eval EXTRA_FLAGS := $(3))
	rm -rf $(BUILD_PATH)
	$(CMAKE) -S $(WHISPER_CPP_DIR) -B $(BUILD_PATH) -DCMAKE_OSX_ARCHITECTURES=$(ARCH) $(CMAKE_COMMON_PARAMS) $(EXTRA_FLAGS)
	$(CMAKE) --build $(BUILD_PATH)
	mkdir -p $(RUNTIME_DIR)/macos-$(ARCH)$(2)
	cp $(BUILD_PATH)/src/libwhisper.dylib $(RUNTIME_DIR)/macos-$(ARCH)$(2)/
	cp $(BUILD_PATH)/ggml/src/libggml.dylib $(RUNTIME_DIR)/macos-$(ARCH)$(2)/
endef

# Linux build targets
.PHONY: linux linux_cuda linux_openvino
linux: $(addprefix linux_,$(LINUX_ARCHS))
linux_%:
	$(call linux_build,$*,,$(AVX_FLAGS))

# Linux CUDA builds
linux_cuda: $(addprefix linux_cuda_,$(LINUX_ARCHS))
linux_cuda_%:
	$(eval ARCH := $(subst linux_cuda_,,$@))
	$(call linux_build,$(ARCH),_cuda,$(AVX_FLAGS) -DGGML_CUDA=ON)

# Linux OpenVINO builds
linux_openvino: $(addprefix linux_openvino_,$(LINUX_ARCHS))
linux_openvino_%:
	$(eval ARCH := $(subst linux_openvino_,,$@))
	$(call linux_build,$(ARCH),_openvino,$(AVX_FLAGS) -DWHISPER_OPENVINO=ON)

# Linux without AVX
linux_noavx: $(addprefix linux_noavx_,$(LINUX_ARCHS))
linux_noavx_%:
	$(eval ARCH := $(subst linux_noavx_,,$@))
	$(call linux_build,$(ARCH),_noavx,$(NO_AVX_FLAGS))

# MacOS build targets
.PHONY: macos macos_coreml
macos: $(addprefix macos_,$(MACOS_ARCHS))
macos_%:
	$(call macos_build,$*,,$(AVX_FLAGS))

# MacOS CoreML builds
macos_coreml: $(addprefix macos_coreml_,$(MACOS_ARCHS))
macos_coreml_%:
	$(call macos_build,$*,_coreml,$(AVX_FLAGS) -DWHISPER_COREML=ON -DWHISPER_COREML_ALLOW_FALLBACK=ON)

# Clean build artifacts
.PHONY: clean
clean:
	rm -rf $(BUILD_DIR)
	rm -rf $(RUNTIME_DIR)

# All builds
.PHONY: all
all: linux macos linux_cuda linux_openvino macos_coreml linux_noavx
