# =============================================================================
# NetworkMemories — MGO2 Nomad Server
# Multi-stage: Maven build → JRE-8 runtime
# Java 8 required (original codebase targets Java 8)
# =============================================================================
FROM maven:3.9-eclipse-temurin-8 AS builder

WORKDIR /build
COPY pom.xml .
# Pre-download dependencies (layer cache)
RUN mvn dependency:go-offline -q

COPY src/ ./src/
RUN mvn clean package -DskipTests -q

# --- Runtime ---
FROM eclipse-temurin:8-jre-alpine

WORKDIR /app
COPY --from=builder /build/target/nomad-*.jar ./nomad.jar
COPY docker/server/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

# Non-root
RUN adduser -D -s /bin/sh nomad && chown -R nomad:nomad /app
USER nomad

EXPOSE 10020 10100 10200 10201

ENTRYPOINT ["/entrypoint.sh"]
