FROM alpine:3.12 as builder

RUN apk add --no-cache make cmake linux-headers g++ git
RUN git clone https://github.com/wez/atomicparsley.git /tmp/atomicparsley

RUN cd /tmp/atomicparsley \
  && cmake . \
  && cmake --build . --config Release

FROM alpine:3.12

RUN apk add libstdc++

COPY --from=builder /tmp/atomicparsley/AtomicParsley /usr/local/bin/AtomicParsley

ENTRYPOINT ["/usr/local/bin/AtomicParsley"]
