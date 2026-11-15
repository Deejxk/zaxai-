FROM node:22-alpine
WORKDIR /app
ENV NODE_ENV=production
COPY package*.json ./
RUN npm ci --omit=dev
COPY . .
EXPOSE 3000 1812/udp 1813/udp
VOLUME ["/app/data"]
CMD ["npm", "start"]
