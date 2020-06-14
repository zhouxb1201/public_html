<template>
  <div class="card">
    <div class="card__header">
      <div class="card__thumb" v-if="thumb || $slots.thumb" @click="click">
        <slot name="thumb">
          <img v-if="lazyLoad" v-lazy="thumb" :key="thumb" class="card__img" pic-type="square">
          <img v-else :src="thumb" class="card__img" :onerror="$ERRORPIC.noSquare">
        </slot>
        <van-tag v-if="tag" mark type="danger" class="tag tag--mark card__tag">{{ tag }}</van-tag>
      </div>
      <div class="card__content">
        <slot name="title">
          <div v-if="title" class="card__title" @click="click">{{ title }}</div>
        </slot>
        <slot name="desc">
          <div v-if="desc" class="card__desc ellipsis">{{ desc }}</div>
        </slot>
        <slot name="tags"/>
        <div class="card__bottom">
          <slot name="bottom" v-if="!$slots.bottomRight">
            <div class="card__price-group">
              <div class="card__price" v-if="price">{{ currency }} {{ price }}</div>
              <div class="card__origin-price" v-if="originPrice">{{ currency }} {{ originPrice }}</div>
            </div>
            <div class="card__num" v-if="num">x {{ num }}</div>
          </slot>
          <div class="card__price-group" v-if="$slots.bottomRight">
            <div class="card__price" v-if="price">{{ currency }} {{ price }}</div>
            <div class="card__origin-price" v-if="originPrice">{{ currency }} {{ originPrice }}</div>
          </div>
          <slot name="bottomRight" v-if="$slots.bottomRight"/>
        </div>
      </div>
    </div>
    <div class="card__footer" v-if="$slots.footer">
      <slot name="footer"/>
    </div>
  </div>
</template>
<script>
export default {
  data() {
    return {};
  },
  props: {
    id: [String, Number],
    tag: String,
    desc: String,
    thumb: String,
    title: String,
    centered: Boolean,
    lazyLoad: Boolean,
    thumbLink: String,
    num: [Number, String],
    price: [Number, String],
    originPrice: [Number, String],
    currency: {
      type: String,
      default: "¥"
    },
    to: [String, Object]
  },
  methods: {
    click() {
      if (this.to) {
        this.$router.push(this.to);
        return;
      }
      if (this.id) {
        this.$router.push("/goods/detail/" + this.id);
      } else if (this.thumbLink) {
        this.$router.push(this.thumbLink);
      }
    }
  }
};
</script>
<style scoped>
.card {
  position: relative;
  color: #323233;
  font-size: 12px;
  padding: 0;
  -webkit-box-sizing: border-box;
  box-sizing: border-box;
  background-color: #ffffff;
  margin-top: 10px;
}

.card:first-child {
  margin-top: 0;
}

.card__header {
  display: -webkit-box;
  display: -ms-flexbox;
  display: flex;
}

.card--center,
.card__thumb {
  -webkit-box-align: center;
  -ms-flex-align: center;
  align-items: center;
  -webkit-box-pack: center;
  -ms-flex-pack: center;
  justify-content: center;
}

.card__thumb {
  position: relative;
  width: 90px;
  height: 90px;
  margin-right: 10px;
  -webkit-box-flex: 0;
  -ms-flex: none;
  flex: none;
  background: #f9f9f9;
  display: flex;
  align-items: center;
  justify-content: center;
}

.card__thumb img {
  border: 0;
  max-width: 100%;
  max-height: 100%;
}

.card__content {
  position: relative;
  -webkit-box-flex: 1;
  -ms-flex: 1;
  flex: 1;
  height: 90px;
  min-width: 0;
}

.card__title {
  line-height: 16px;
  max-height: 32px;
  height: 32px;
  font-weight: 700;
  overflow: hidden;
  text-overflow: ellipsis;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
}

.card__desc,
.card__title {
  word-break: break-all;
}

.card__desc {
  width: 100%;
  overflow: hidden;
  color: #7d7e80;
  max-height: 20px;
  line-height: 20px;
  overflow: hidden;
  white-space: nowrap;
  text-overflow: ellipsis;
}

.card__bottom {
  position: absolute;
  bottom: 0;
  left: 0;
  width: 100%;
  line-height: 18px;
  display: flex;
  justify-content: space-between;
}

.card__footer {
  text-align: right;
  -webkit-box-flex: 0;
  -ms-flex: none;
  flex: none;
}

.card__num {
  float: right;
}

.card__price-group {
  white-space: nowrap;
  display: flex;
  align-items: center;
}

.card__price {
  display: inline-block;
  color: #ff454e;
  font-weight: 700;
}
</style>
