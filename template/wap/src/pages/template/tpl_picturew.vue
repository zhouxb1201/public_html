<template>
  <div :class="item.id">
    <div class="vui-cube" :style="item_background" v-if="item.params.row == '1'">
      <router-link
        tag="div"
        :to="toArray(item.data)[0].linkurl"
        v-if="count(item.data) == 1"
        class="item e-handle"
        :style="item_padding"
      >
        <img
          v-lazy="toArray(item.data)[0].imgurl"
          :key="toArray(item.data)[0].imgurl"
          pic-type="rectangle"
        />
      </router-link>
      <div v-if="count(item.data) > 1">
        <router-link
          tag="div"
          :to="toArray(item.data)[0].linkurl"
          class="vui-cube-left item e-handle"
          :style="item_padding"
        >
          <img
            v-lazy="toArray(item.data)[0].imgurl"
            :key="toArray(item.data)[0].imgurl"
            pic-type="square"
          />
        </router-link>
        <div class="vui-cube-right">
          <router-link
            tag="div"
            :to="toArray(item.data)[1].linkurl"
            v-if="count(item.data) == 2"
            class="item e-handle"
            :style="item_padding"
          >
            <img
              v-lazy="toArray(item.data)[1].imgurl"
              :key="toArray(item.data)[1].imgurl"
              pic-type="square"
            />
          </router-link>
          <div v-if="count(item.data) > 2">
            <router-link
              tag="div"
              :to="toArray(item.data)[1].linkurl"
              class="vui-cube-right1 item e-handle"
              :style="item_padding"
            >
              <img
                v-lazy="toArray(item.data)[1].imgurl"
                :key="toArray(item.data)[1].imgurl"
                pic-type="rectangle"
              />
            </router-link>
            <div class="vui-cube-right2">
              <router-link
                tag="div"
                :to="toArray(item.data)[2].linkurl"
                v-if="count(item.data) == 3"
                class="item e-handle"
                :style="item_padding"
              >
                <img
                  v-lazy="toArray(item.data)[2].imgurl"
                  :key="toArray(item.data)[2].imgurl"
                  pic-type="rectangle"
                />
              </router-link>
              <router-link
                tag="div"
                :to="toArray(item.data)[2].linkurl"
                v-if="count(item.data) > 3"
                class="item e-handle left"
                :style="item_padding"
              >
                <img
                  v-lazy="toArray(item.data)[2].imgurl"
                  :key="toArray(item.data)[2].imgurl"
                  pic-type="square"
                />
              </router-link>
              <router-link
                tag="div"
                :to="toArray(item.data)[3].linkurl"
                v-if="count(item.data) >= 4"
                class="item e-handle right"
                :style="item_padding"
              >
                <img
                  v-lazy="toArray(item.data)[3].imgurl"
                  :key="toArray(item.data)[3].imgurl"
                  pic-type="square"
                />
              </router-link>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div
      class="vui-picturew"
      :style="item_background"
      :class="'row-'+item.params.row"
      v-if="item.params.row > 1"
    >
      <router-link
        tag="div"
        :to="child.linkurl"
        class="item e-handle"
        :style="item_padding"
        v-for="(child,index) in item.data"
        :key="index"
      >
        <img v-lazy="child.imgurl" :key="child.imgurl" pic-type="square" />
      </router-link>
    </div>
  </div>
</template>

<script>
export default {
  name: "tpl_picturew",
  data() {
    return {
      item_background: {
        background: this.item.style.background
      },
      item_padding: {
        padding:
          this.item.style.paddingtop +
          "px" +
          " " +
          this.item.style.paddingleft +
          "px"
      }
    };
  },
  props: {
    type: [String, Number],
    item: Object
  },
  created() {},
  methods: {
    toArray(obj) {
      let newArray = [];
      for (let i in obj) {
        newArray.push(obj[i]);
      }
      return newArray;
    },
    count(obj) {
      if (typeof obj === "undefined") {
        return 0;
      }
      let jsonlen = 0;
      for (let i in obj) {
        jsonlen++;
      }
      return jsonlen;
    }
  }
};
</script>

<style scoped>
.vui-cube {
  height: 0;
  width: 100%;
  margin: 0;
  padding-bottom: 50%;
  position: relative;
  overflow: hidden;
}

.vui-cube .vui-cube-left {
  width: 50%;
  height: 100%;
  position: absolute;
  top: 0;
  left: 0;
}

.vui-cube img {
  width: 100%;
  height: 100%;
}

.vui-cube .vui-cube-right {
  width: 50%;
  height: 100%;
  position: absolute;
  top: 0;
  left: 50%;
}

.vui-cube .vui-cube-right1 {
  width: 100%;
  height: 50%;
  position: absolute;
  top: 0;
  left: 0;
}

.vui-cube .vui-cube-right2 {
  width: 100%;
  height: 50%;
  position: absolute;
  top: 50%;
  left: 0;
}

.vui-cube .vui-cube-right2 .left {
  width: 50%;
  height: 100%;
  position: absolute;
  top: 0;
  left: 0;
}

.vui-cube .vui-cube-right2 .right {
  width: 50%;
  height: 100%;
  position: absolute;
  top: 0;
  left: 50%;
}

.vui-picturew {
  height: auto;
  display: block;
  overflow: hidden;
}

.vui-picturew .item {
  height: auto;
  width: 100%;
  display: block;
  float: left;
}

.vui-picturew .item img {
  display: block;
  max-width: 100%;
  max-height: 100%;
}

.vui-picturew.row-2 .item {
  width: 50%;
}

.vui-picturew.row-3 .item {
  width: 33.33333%;
}

.vui-picturew.row-4 .item {
  width: 25%;
}
</style>
